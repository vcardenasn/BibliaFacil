<?php

// API.Bible runtime — versions.api_bible_id enlaza una versión del catálogo
// con su bibleId de api.scripture.api.bible. Cuando está set, el contenido se
// sirve vía API (con caché en disco + FUMS) en vez de la tabla verses — sin
// copia local permanente, como exigen los ToS de ABS para contenido licenciado.

return [
    'up' => function (PDO $pdo): void {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $exists = (bool) $pdo->query(
                "SELECT 1 FROM pragma_table_info('versions') WHERE name = 'api_bible_id'"
            )->fetch();
            if (!$exists) {
                $pdo->exec('ALTER TABLE versions ADD COLUMN api_bible_id VARCHAR(40) NULL');
            }
        } else {
            $pdo->exec('ALTER TABLE versions ADD COLUMN api_bible_id VARCHAR(40) NULL');
        }
    },
    'down' => function (PDO $pdo): void {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $exists = (bool) $pdo->query(
                "SELECT 1 FROM pragma_table_info('versions') WHERE name = 'api_bible_id'"
            )->fetch();
            if ($exists) {
                $pdo->exec('ALTER TABLE versions DROP COLUMN api_bible_id');
            }
        } else {
            $pdo->exec('ALTER TABLE versions DROP COLUMN api_bible_id');
        }
    },
];
