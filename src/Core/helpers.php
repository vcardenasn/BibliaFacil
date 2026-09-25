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
    return url('assets/' . ltrim($path, '/'));
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
    require BASE_PATH . '/app/Views/layout.php';
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
