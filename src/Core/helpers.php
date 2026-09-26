<?php

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    return ($value === false || $value === null || $value === '') ? $default : $value;
}

function config(string $key, mixed $default = null): mixed
{
    static $configs = [];
    [$file, $path] = explode('.', $key, 2) + [1 => null];
    if (!isset($configs[$file])) {
        $filePath = CONFIG_PATH . '/' . $file . '.php';
        $configs[$file] = is_file($filePath) ? require $filePath : [];
    }
    if ($path === null) {
        return $configs[$file];
    }
    $value = $configs[$file];
    foreach (explode('.', $path) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    $script = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    return ($script === '/' ? '' : $script) . $path;
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = BASE_PATH . '/public/assets/' . $path;
    // Fingerprint por contenido (US-210): la URL cambia al editar el asset
    // → el navegador puede cachearlo largo sin riesgo de servir uno viejo.
    $v = is_file($file) ? '?v=' . substr(md5_file($file), 0, 8) : '';
    return url('assets/' . $path) . $v;
}

function view(string $name, array $data = []): void
{
    $viewFile = BASE_PATH . '/app/Views/' . $name . '.php';
    if (!is_file($viewFile)) {
        throw new RuntimeException("Vista no encontrada: {$name}");
    }
    extract($data, EXTR_SKIP);
    ob_start();
    require $viewFile;
    $content = ob_get_clean();
    $visits = \Biblia\Core\Stats::bump('pv'); // [hoy, total] o null si falta la tabla

    // EPIC 16 — métricas agregadas (anónimas, tolerantes a fallos)
    \Biblia\Core\Metrics::session();
    \Biblia\Core\Metrics::bump('pv', $name);
    if (!empty($data['version']['code'])) {
        \Biblia\Core\Metrics::bump('ver', $data['version']['code']);
    }
    if ($name === 'reader' && !empty($data['book']['slug'])) {
        \Biblia\Core\Metrics::bump('cap', $data['version']['code'] . ':' . $data['book']['slug'] . ':' . ($data['chapter'] ?? 0));
    }
    if ($name === 'search' && ($data['q'] ?? '') !== '') {
        \Biblia\Core\Metrics::bump('search', $data['version']['code'] ?? '');
    }
    if ($name === 'juego' && !empty($data['slug'])) {
        \Biblia\Core\Metrics::bump('game', $data['slug']);
    }

    $meta = \Biblia\Core\Seo::build($name, $data); // EPIC 18 — meta dinámico por vista
    require BASE_PATH . '/app/Views/layout.php';
}

/**
 * HTML del cuerpo de un versículo: palabras de Jesús en <em class="wj">
 * y capitular opcional (versículo 1). Si el rango wj cubre el primer
 * carácter, se omite la capitular para no partir el span.
 */
function verseHtml(string $text, ?string $wjJson, bool $dropcap = false): string
{
    $ranges = $wjJson ? json_decode($wjJson, true) : null;
    $coversStart = is_array($ranges) && isset($ranges[0]) && (int) $ranges[0][0] === 0;
    if (!$dropcap || $coversStart) {
        return \Biblia\Bible\VerseText::render($text, $wjJson);
    }
    if (is_array($ranges)) {
        $wjJson = json_encode(array_map(fn ($r) => [$r[0] - 1, $r[1]], $ranges));
    }
    return '<span class="dropcap">' . e(mb_substr($text, 0, 1)) . '</span>'
        . \Biblia\Bible\VerseText::render(mb_substr($text, 1), $wjJson);
}

/** Barra de compartir (US-202): links server-side, sin JS obligatorio. */
function sharebar(string $absUrl, string $text): string
{
    $u = rawurlencode($absUrl);
    $t = rawurlencode($text);
    $btns = [
        ['wa', 'WhatsApp', '💬', "https://wa.me/?text={$t}%20{$u}"],
        ['tg', 'Telegram', '✈️', "https://t.me/share/url?url={$u}&text={$t}"],
        ['x', 'X', '𝕏', "https://twitter.com/intent/tweet?text={$t}&url={$u}"],
        ['fb', 'Facebook', 'ⓕ', "https://www.facebook.com/sharer/sharer.php?u={$u}"],
    ];
    $h = '<div class="sharebar">';
    foreach ($btns as [$cls, $name, $ico, $href]) {
        $h .= '<a class="share-btn share-' . $cls . '" href="' . e($href) . '" target="_blank" rel="noopener" title="Compartir en ' . $name . '" aria-label="' . $name . '">' . $ico . '</a>';
    }
    $h .= '<button type="button" class="share-btn share-cp" data-copy="' . e($absUrl) . '" title="Copiar enlace" aria-label="Copiar enlace">🔗</button>';
    return $h . '</div>';
}

/** Quita acentos para comparaciones (búsqueda, parser). */
function unaccent(string $value): string
{
    return strtr(mb_strtolower($value, 'UTF-8'), [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ü' => 'u', 'ñ' => 'n', 'à' => 'a', 'è' => 'e', 'ì' => 'i',
        'ò' => 'o', 'ù' => 'u',
    ]);
}
