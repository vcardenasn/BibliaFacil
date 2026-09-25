<?php

/**
 * Genera database/schema.sql (MySQL, importable por phpMyAdmin) desde
 * config/books.php + config/versions.php. Correr al cambiar el catálogo:
 *   php scripts/build_schema.php
 */

require __DIR__ . '/../bootstrap.php';

$books = require CONFIG_PATH . '/books.php';
$versions = require CONFIG_PATH . '/versions.php';

$esc = function (mixed $s): string {
    return $s === null ? 'NULL' : "'" . str_replace("'", "\\'", (string) $s) . "'";
};

$sql = <<<'SQL'
-- BibliaFacil — schema.sql (MySQL, importable por phpMyAdmin)
-- Estructura + seeds de versions/books. Los versículos se cargan con
-- scripts/import_bible.php (fuentes en database/sources/).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    batch INT NOT NULL,
    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (name, batch) VALUES ('0001_create_biblia.php', 1)
ON DUPLICATE KEY UPDATE name = name;

CREATE TABLE IF NOT EXISTS versions (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(160) NOT NULL,
    language CHAR(3) NOT NULL DEFAULT 'es',
    copyright VARCHAR(500) NULL,
    license VARCHAR(60) NOT NULL DEFAULT 'unknown',
    license_status VARCHAR(20) NOT NULL DEFAULT 'open',
    source_url VARCHAR(500) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_versions_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS books (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ord INT NOT NULL,
    osis VARCHAR(8) NOT NULL,
    name VARCHAR(60) NOT NULL,
    slug VARCHAR(60) NOT NULL,
    aliases VARCHAR(500) NOT NULL DEFAULT '',
    testament VARCHAR(2) NOT NULL,
    chapters INT NOT NULL,
    UNIQUE KEY uq_books_ord (ord),
    UNIQUE KEY uq_books_osis (osis),
    UNIQUE KEY uq_books_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS verses (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    version_id INT NOT NULL,
    book_id INT NOT NULL,
    chapter INT NOT NULL,
    verse INT NOT NULL,
    text TEXT NOT NULL,
    UNIQUE KEY uq_verses_ref (version_id, book_id, chapter, verse),
    KEY idx_verses_chapter (version_id, book_id, chapter),
    FULLTEXT KEY ft_verses_text (text),
    CONSTRAINT fk_verses_version FOREIGN KEY (version_id) REFERENCES versions(id),
    CONSTRAINT fk_verses_book FOREIGN KEY (book_id) REFERENCES books(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SQL;

$sql .= "\n-- Seeds: versions\nINSERT INTO versions (code, name, language, copyright, license, license_status, source_url, active) VALUES\n";
$rows = [];
foreach ($versions as $v) {
    $rows[] = '(' . implode(', ', [
        $esc($v['code']), $esc($v['name']), $esc($v['language']), $esc($v['copyright']),
        $esc($v['license']), $esc($v['license_status']), $esc($v['source_url']), (int) $v['active'],
    ]) . ')';
}
$sql .= implode(",\n", $rows) . "\nON DUPLICATE KEY UPDATE name = VALUES(name), license_status = VALUES(license_status);\n";

$sql .= "\n-- Seeds: books (66, canon protestante)\nINSERT INTO books (ord, osis, name, slug, aliases, testament, chapters) VALUES\n";
$rows = [];
foreach ($books as $b) {
    $rows[] = '(' . implode(', ', [
        $b['ord'], $esc($b['osis']), $esc($b['name']), $esc($b['slug']),
        $esc($b['aliases']), $esc($b['testament']), $b['chapters'],
    ]) . ')';
}
$sql .= implode(",\n", $rows) . "\nON DUPLICATE KEY UPDATE osis = VALUES(osis), name = VALUES(name), slug = VALUES(slug), aliases = VALUES(aliases), chapters = VALUES(chapters);\n";

file_put_contents(BASE_PATH . '/database/schema.sql', $sql);
echo 'schema.sql generado: ' . strlen($sql) . " bytes\n";
