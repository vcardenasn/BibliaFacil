<?php

define('BASE_PATH', __DIR__);
define('SRC_PATH', BASE_PATH . '/src');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');

$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    if ($env === false) {
        throw new RuntimeException('Invalid .env file.');
    }
    foreach ($env as $key => $value) {
        $processValue = getenv($key);
        $_ENV[$key] = $processValue === false ? $value : $processValue;
    }
}

spl_autoload_register(function (string $class): void {
    $prefix = 'Biblia\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = SRC_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require $path;
    }
});

require SRC_PATH . '/Core/helpers.php';

$appConfig = config('app');
date_default_timezone_set($appConfig['timezone']);
ini_set('display_errors', $appConfig['display_errors'] ? '1' : '0');
error_reporting($appConfig['error_reporting']);

set_error_handler(function (int $level, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $level)) {
        return false;
    }
    throw new ErrorException($message, 0, $level, $file, $line);
});

set_exception_handler(function (Throwable $exception) use ($appConfig): void {
    error_log($exception::class . ': ' . $exception->getMessage());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $exception::class . ': ' . $exception->getMessage() . PHP_EOL);
        exit(1);
    }
    if ($exception instanceof Biblia\Core\FeatureDisabledException) {
        // 503 + flag: el consumidor distingue "apagado" de "roto" sin exponer internals.
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(503);
        echo json_encode(['error' => 'feature_disabled', 'flag' => $exception->flag]);
        return;
    }
    http_response_code(500);
    if ($appConfig['display_errors']) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $exception::class . ': ' . $exception->getMessage() . "\n" . $exception->getTraceAsString();
        return;
    }
    view('error', ['title' => 'Error', 'message' => 'Ocurrió un error interno. Intenta de nuevo.']);
});

if (PHP_SAPI === 'cli') {
    return;
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
header_remove('X-Powered-By');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
