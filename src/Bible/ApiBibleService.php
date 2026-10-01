<?php

namespace Biblia\Bible;

use Throwable;

/**
 * Servicio runtime de API.Bible para versiones con licencia vía esa plataforma
 * (versions.api_bible_id). El contenido NO se guarda en la BD — se cachea en
 * disco con TTL corto (§11 Content Recency: máx 30 días) y cada despliegue
 * reporta su fumsToken al sistema FUMS de ABS (§3.3/§14, obligatorio en
 * webapps). Sin API_BIBLE_KEY el servicio devuelve null y la versión queda
 * vacía — nunca falla en runtime.
 */
final class ApiBibleService
{
    private const FUMS_URL = 'https://fums.api.bible/f3';
    private const CACHE_TTL = 604800; // 7 días (bien por debajo del máx. 30)
    private const FUMS_BATCH = 50;

    /** @var string[] fumsTokens recolectados en este request */
    private static array $fumsTokens = [];
    private static bool $shutdownHooked = false;
    private static ?string $sessionId = null;
    private static ?string $deviceId = null;

    private function __construct(private ApiBibleClient $client)
    {
        if (!self::$shutdownHooked) {
            self::$shutdownHooked = true;
            register_shutdown_function([self::class, 'reportFums']);
        }
    }

    /** null si no hay clave — la app no debe romperse por falta de .env. */
    public static function make(): ?self
    {
        $key = trim((string) ($_ENV['API_BIBLE_KEY'] ?? ''));
        return $key === '' ? null : new self(new ApiBibleClient($key));
    }

    /** @return array<int,array{verse:int,text:string,wj:?string}> */
    public function chapterVerses(string $bibleId, string $osis, int $chapter): array
    {
        $data = $this->chapterData($bibleId, strtoupper($osis) . '.' . $chapter);
        return $data['verses'];
    }

    /** Versículo puntual — aprovecha la caché de capítulo (1 llamada sirve todo). */
    public function verse(string $bibleId, string $osis, int $chapter, int $verse): ?array
    {
        foreach ($this->chapterVerses($bibleId, $osis, $chapter) as $row) {
            if ($row['verse'] === $verse) {
                return $row;
            }
        }
        return null;
    }

    /** @return array<int,array{osis:string,chapter:int,verse:int,text:string}> */
    public function search(string $bibleId, string $query, int $limit = 50): array
    {
        $data = $this->client->get("/bibles/{$bibleId}/search", [
            'query' => $query,
            'limit' => $limit,
        ]);
        $out = [];
        foreach (($data['verses'] ?? []) as $v) {
            if (!preg_match('/^([A-Z0-9]+)\.(\d+)\.(\d+)$/', (string) ($v['id'] ?? ''), $m)) {
                continue;
            }
            $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) ($v['text'] ?? ''))));
            if ($text === '') {
                continue;
            }
            $out[] = [
                'osis' => $m[1],
                'chapter' => (int) $m[2],
                'verse' => (int) $m[3],
                'text' => $text,
            ];
        }
        return $out;
    }

    // ---------------------------------------------------------------------

    /** @return array{verses:array,fums:?string} con caché en disco + TTL */
    private function chapterData(string $bibleId, string $chapterId): array
    {
        $file = $this->cacheFile($bibleId, $chapterId);
        if (is_file($file) && (time() - (int) filemtime($file)) < self::CACHE_TTL) {
            $cached = json_decode((string) file_get_contents($file), true);
            if (is_array($cached) && !empty($cached['verses'])) {
                $this->recordFums($cached['fums'] ?? null);
                return $cached;
            }
            // Archivo con versículos vacíos (parse bug, respuesta rara):
            // se ignora y re-consulta la API en vez de servir vacío 7 días.
        }

        $res = $this->client->getFull("/bibles/{$bibleId}/chapters/{$chapterId}", [
            'content-type' => 'json',
            'include-notes' => 'false',
            'include-titles' => 'false',
            'include-chapter-numbers' => 'false',
            'include-verse-numbers' => 'false',
            'include-verse-spans' => 'false',
        ]);
        $fums = $res['meta']['fumsToken'] ?? $res['meta']['fumsId'] ?? null;
        $rows = [];
        foreach (self::parseChapter($res['data']['content'] ?? []) as $num => $raw) {
            [$text, $wj] = VerseText::split($raw);
            if ($text !== '') {
                $rows[] = ['verse' => $num, 'text' => $text, 'wj' => $wj];
            }
        }

        $payload = ['verses' => $rows, 'fums' => $fums];
        if ($rows !== []) {
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0755, true);
            }
            file_put_contents($file, json_encode($payload));
        }
        $this->recordFums($fums);
        return $payload;
    }

    private function cacheFile(string $bibleId, string $chapterId): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '', $bibleId . '__' . $chapterId);
        return STORAGE_PATH . '/apibible/' . $safe . '.json';
    }

    private function recordFums(?string $token): void
    {
        if (is_string($token) && $token !== '') {
            self::$fumsTokens[] = $token;
        }
    }

    // ------------------------------ FUMS -----------------------------------

    /** Reporta los fumsTokens al endpoint manual de FUMS (best-effort). */
    public static function reportFums(): void
    {
        $tokens = array_values(array_unique(array_filter(self::$fumsTokens)));
        self::$fumsTokens = [];
        if (!$tokens) {
            return;
        }
        $base = self::FUMS_URL . '?dId=' . urlencode(self::deviceId())
            . '&sId=' . urlencode(self::sessionId());
        foreach (array_chunk($tokens, self::FUMS_BATCH) as $chunk) {
            $url = $base . implode('', array_map(
                fn ($t) => '&t=' . urlencode($t),
                $chunk
            ));
            $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
            @file_get_contents($url, false, $ctx);
        }
    }

    /** ID anónimo estable de la instalación (no PII, no del visitante). */
    private static function deviceId(): string
    {
        if (self::$deviceId !== null) {
            return self::$deviceId;
        }
        $file = STORAGE_PATH . '/apibible_device';
        if (is_file($file)) {
            $id = trim((string) file_get_contents($file));
        } else {
            $id = bin2hex(random_bytes(16));
            if (!is_dir(STORAGE_PATH)) {
                mkdir(STORAGE_PATH, 0755, true);
            }
            @file_put_contents($file, $id);
        }
        return self::$deviceId = $id;
    }

    /** ID anónimo por sesión de navegación (cookie de sesión, sin PII). */
    private static function sessionId(): string
    {
        if (self::$sessionId !== null) {
            return self::$sessionId;
        }
        $sid = (string) ($_COOKIE['bf_sid'] ?? '');
        if (!preg_match('/^[a-f0-9]{32}$/', $sid)) {
            $sid = bin2hex(random_bytes(16));
            if (!headers_sent()) {
                @setcookie('bf_sid', $sid, [
                    'expires' => 0, 'path' => '/', 'httponly' => true,
                    'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']),
                ]);
            }
        }
        return self::$sessionId = $sid;
    }

    /**
     * Aplana el JSON de capítulo a [versículo => texto con sentinels [wj]].
     * Formato real API.Bible: pedimos include-verse-numbers=false, así que no
     * hay nodos name="verse" — cada nodo text lleva attrs.verseId="JHN.3.1"
     * que da el número de versículo. char con attrs.style="wj" marca las
     * palabras de Jesús.
     */
    public static function parseChapter(array $nodes): array
    {
        $out = [];
        $current = null;
        $walk = function (array $nodes, bool $wj) use (&$walk, &$out, &$current): void {
            foreach ($nodes as $node) {
                $type = $node['type'] ?? '';
                $name = $node['name'] ?? '';
                $attrs = $node['attrs'] ?? [];
                if ($name === 'verse') {
                    $n = (int) ($attrs['number'] ?? $node['number'] ?? 0);
                    if ($n > 0) {
                        $current = $n;
                        $out[$current] = $out[$current] ?? '';
                    }
                    continue; // los items internos solo repintan el número
                }
                if ($type === 'text') {
                    $vid = (string) ($attrs['verseId'] ?? '');
                    if (preg_match('/\.(\d+)$/', $vid, $m)) {
                        $current = (int) $m[1];
                        $out[$current] = $out[$current] ?? '';
                    }
                    if ($current !== null) {
                        $out[$current] .= ($wj ? '[wj]' : '') . ($node['text'] ?? '') . ($wj ? '[/wj]' : '');
                    }
                }
                if (!empty($node['items']) && is_array($node['items'])) {
                    $walk($node['items'], $wj || ($name === 'char' && ($attrs['style'] ?? $node['style'] ?? '') === 'wj'));
                }
            }
        };
        $walk($nodes, false);
        return $out;
    }
}
