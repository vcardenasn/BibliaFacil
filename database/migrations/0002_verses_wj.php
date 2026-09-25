<?php

// US-0xx — palabras de Jesús en rojo: ranges [ini,len] JSON por versículo.
// Portable SQLite/MySQL; idempotente vía chequeo de columna.

return [
    'up' => function (PDO $pdo): void {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $exists = $driver === 'sqlite'
            ? (bool) $pdo->query("SELECT 1 FROM pragma_table_info('verses') WHERE name = 'wj'")->fetch()
            : (bool) $pdo->query("SHOW COLUMNS FROM verses LIKE 'wj'")->fetch();
        if (!$exists) {
            $pdo->exec('ALTER TABLE verses ADD COLUMN wj TEXT NULL');
        }
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec('ALTER TABLE verses DROP COLUMN wj');
    },
];
