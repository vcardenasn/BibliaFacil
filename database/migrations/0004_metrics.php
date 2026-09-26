<?php

// EPIC 16 — métricas privacy-first:
// metrics_daily(d, metric, dim, n) → agregados por día/métrica/dimensión.
// metrics_dau(d, h) → hashes de "sesión" rotativos por día (nunca IP cruda).
// Sin cookies, sin PII: el hash mezcla IP+UA+día+salt y caduca a diario.

return [
    'up' => function (PDO $pdo): void {
        $mysql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
        $engine = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS metrics_daily (
                metric VARCHAR(40) NOT NULL,
                dim VARCHAR(120) NOT NULL DEFAULT '',
                d DATE NOT NULL,
                n INT NOT NULL DEFAULT 0,
                PRIMARY KEY (metric, dim, d)
            ){$engine}
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS metrics_dau (
                d DATE NOT NULL,
                h CHAR(64) NOT NULL,
                PRIMARY KEY (d, h)
            ){$engine}
        ");
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS metrics_daily');
        $pdo->exec('DROP TABLE IF EXISTS metrics_dau');
    },
];
