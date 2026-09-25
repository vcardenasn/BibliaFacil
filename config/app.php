<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'BibliaFacil',
    // default production-safe — display_errors solo con APP_ENV=local.
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'url' => rtrim($_ENV['APP_URL'] ?? 'http://127.0.0.1:8462', '/'),
    'display_errors' => ($_ENV['APP_ENV'] ?? 'production') === 'local',
    'error_reporting' => E_ALL,
    'timezone' => 'America/Mexico_City',
    // Versión que se muestra por defecto a visitantes nuevos.
    'default_version' => $_ENV['DEFAULT_VERSION'] ?? 'rvr1909',
];
