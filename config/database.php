<?php

return [
    'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'name' => $_ENV['DB_NAME'] ?? 'bibliafacil',
    'user' => $_ENV['DB_USER'] ?? 'bibliafacil',
    'pass' => $_ENV['DB_PASS'] ?? '',
    'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    // Ruta absoluta: si DB_SQLITE_PATH es relativa se resuelve contra BASE_PATH
    // (php -S -t public tiene otro CWD y crearía un sqlite vacío).
    'sqlite_path' => (function () {
        $p = $_ENV['DB_SQLITE_PATH'] ?? STORAGE_PATH . '/biblia.sqlite';
        return ($p !== ':memory:' && $p[0] !== '/') ? BASE_PATH . '/' . $p : $p;
    })(),
];
