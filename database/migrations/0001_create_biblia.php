<?php

// EPIC 02 / US-003 — esquema núcleo: versions + books + verses.
// `up` es callable para emitir DDL portable MySQL/SQLite (tests corren en SQLite).

return [
    'up' => function (PDO $pdo): void {
        $mysql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';

        $pk = $mysql ? 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $engine = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
        $datetime = 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $text = 'TEXT NOT NULL';
        $bool = 'TINYINT(1) NOT NULL DEFAULT 1';

        $pdo->exec("
            CREATE TABLE versions (
                id {$pk},
                code VARCHAR(20) NOT NULL,
                name VARCHAR(160) NOT NULL,
                language CHAR(3) NOT NULL DEFAULT 'es',
                copyright VARCHAR(500) NULL,
                license VARCHAR(60) NOT NULL DEFAULT 'unknown',
                license_status VARCHAR(20) NOT NULL DEFAULT 'open',
                source_url VARCHAR(500) NULL,
                active {$bool},
                created_at {$datetime}
            ){$engine}
        ");
        $pdo->exec('CREATE UNIQUE INDEX uq_versions_code ON versions (code)');

        $pdo->exec("
            CREATE TABLE books (
                id {$pk},
                ord INT NOT NULL,
                osis VARCHAR(8) NOT NULL,
                name VARCHAR(60) NOT NULL,
                slug VARCHAR(60) NOT NULL,
                aliases VARCHAR(500) NOT NULL DEFAULT '',
                testament VARCHAR(2) NOT NULL,
                chapters INT NOT NULL
            ){$engine}
        ");
        $pdo->exec('CREATE UNIQUE INDEX uq_books_ord ON books (ord)');
        $pdo->exec('CREATE UNIQUE INDEX uq_books_osis ON books (osis)');
        $pdo->exec('CREATE UNIQUE INDEX uq_books_slug ON books (slug)');

        $pdo->exec("
            CREATE TABLE verses (
                id {$pk},
                version_id INT NOT NULL,
                book_id INT NOT NULL,
                chapter INT NOT NULL,
                verse INT NOT NULL,
                text {$text},
                FOREIGN KEY (version_id) REFERENCES versions(id),
                FOREIGN KEY (book_id) REFERENCES books(id)
            ){$engine}
        ");
        $pdo->exec('CREATE UNIQUE INDEX uq_verses_ref ON verses (version_id, book_id, chapter, verse)');
        $pdo->exec('CREATE INDEX idx_verses_chapter ON verses (version_id, book_id, chapter)');

        if ($mysql) {
            // FULLTEXT solo en MySQL — la búsqueda en SQLite usa LIKE.
            $pdo->exec('CREATE FULLTEXT INDEX ft_verses_text ON verses (text)');
        }
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS verses');
        $pdo->exec('DROP TABLE IF EXISTS books');
        $pdo->exec('DROP TABLE IF EXISTS versions');
    },
];
