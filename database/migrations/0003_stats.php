<?php

// EPIC 16 (mínimo) — contador de visitas: agregados diarios por métrica.
// stats(metric, d, n) → una fila por métrica/día; el contador es anónimo.

return [
    'up' => function (PDO $pdo): void {
        $mysql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
        $engine = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS stats (
                metric VARCHAR(40) NOT NULL,
                d DATE NOT NULL,
                n INT NOT NULL DEFAULT 0,
                PRIMARY KEY (metric, d)
            ){$engine}
        ");
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS stats');
    },
];
