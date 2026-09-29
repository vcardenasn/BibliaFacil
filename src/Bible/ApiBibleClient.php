<?php

namespace Biblia\Bible;

use RuntimeException;

/**
 * Cliente mínimo para api.scripture.api.bible (API.Bible / DBL).
 *
 * Uso: ApiBibleService (runtime, con caché corta + FUMS) y scripts admin.
 * Nada del texto bíblico va a la BD — el ToS exige recency ≤30 días y
 * remoción en 72h si la licencia termina; por eso solo se cachea en disco.
 *
 * La clave va en .env como API_BIBLE_KEY (nunca se commitea).
 */
final class ApiBibleClient
{
    private const BASE = 'https://api.scripture.api.bible/v1';

    public function __construct(
        private string $apiKey,
        private int $sleepMs = 250,
        private int $maxRetries = 4
    ) {
        if ($this->apiKey === '') {
            throw new RuntimeException('Falta API_BIBLE_KEY en .env');
        }
    }

    /** @return array<string,mixed> payload decodificado del campo "data" */
    public function get(string $path, array $query = []): array
    {
        $res = $this->getFull($path, $query);
        return $res['data'] ?? [];
    }

    /**
     * Respuesta completa (data + meta). El meta trae fumsId — requerido por
     * los ToS para reportar cada despliegue de contenido al sistema FUMS.
     */
    public function getFull(string $path, array $query = []): array
    {
        $url = self::BASE . $path . ($query ? '?' . http_build_query($query) : '');
        $attempt = 0;
        while (true) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['api-key: ' . $this->apiKey],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'BibliaFacil/1.0',
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if ($status === 200 && $body !== false) {
                $data = json_decode((string) $body, true);
                if (is_array($data) && array_key_exists('data', $data)) {
                    usleep($this->sleepMs * 1000);
                    return $data;
                }
                throw new RuntimeException("Respuesta inválida de API.Bible para {$path}");
            }
            // 429/5xx: reintenta con backoff; el resto es error fatal.
            if (($status === 429 || $status >= 500) && $attempt < $this->maxRetries) {
                $attempt++;
                usleep((int) (1000 * 1000 * min(30, 2 ** $attempt)));
                continue;
            }
            throw new RuntimeException("API.Bible {$path} → HTTP {$status}: " . substr((string) $body, 0, 200));
        }
    }

    /** Biblias disponibles; filtra por idioma ISO-639-3 (spa, eng…). */
    public function bibles(?string $language = null): array
    {
        return $this->get('/bibles', $language ? ['language' => $language] : []);
    }

    public function bible(string $bibleId): array
    {
        return $this->get("/bibles/{$bibleId}");
    }

    /** Libros de una biblia (id = código USFM: GEN, EXO…). */
    public function books(string $bibleId): array
    {
        return $this->get("/bibles/{$bibleId}/books");
    }

    /** Capítulos de un libro (id tipo GEN.1). */
    public function chapters(string $bibleId, string $bookId): array
    {
        return $this->get("/bibles/{$bibleId}/books/{$bookId}/chapters");
    }

    /**
     * Contenido JSON de un capítulo: nodos anidados con
     * verse (attrs.number), text y char(style=wj) para palabras de Jesús.
     */
    public function chapter(string $bibleId, string $chapterId): array
    {
        return $this->get("/bibles/{$bibleId}/chapters/{$chapterId}", [
            'content-type' => 'json',
            'include-notes' => 'false',
            'include-titles' => 'false',
            'include-chapter-numbers' => 'false',
            'include-verse-numbers' => 'false',
            'include-verse-spans' => 'false',
        ]);
    }
}
