<?php

// EPIC 02 / US-011+012 — seed idempotente de versions + books (66).
// Los versículos NO van aquí: se cargan con scripts/import_bible.php.

require __DIR__ . '/../../bootstrap.php';

use Biblia\Core\Database;

$pdo = Database::getPdo();
$books = require CONFIG_PATH . '/books.php';
$versions = require CONFIG_PATH . '/versions.php';

// --- versions ---------------------------------------------------------------
$checkV = $pdo->prepare('SELECT id FROM versions WHERE code = :code');
$insV = $pdo->prepare(
    'INSERT INTO versions (code, name, language, copyright, license, license_status, source_url, active)
     VALUES (:code, :name, :language, :copyright, :license, :license_status, :source_url, :active)'
);
$updV = $pdo->prepare(
    'UPDATE versions SET name = :name, language = :language, copyright = :copyright,
        license = :license, license_status = :license_status, source_url = :source_url
     WHERE code = :code'
);
$vCount = 0;
foreach ($versions as $v) {
    $checkV->execute(['code' => $v['code']]);
    if ($checkV->fetch()) {
        // active no se sobreescribe: es decisión operativa del admin.
        $updV->execute($v);
        continue;
    }
    $insV->execute($v);
    $vCount++;
}
echo "Versions: {$vCount} nuevas (" . count($versions) . " total en catálogo)\n";

// --- books ------------------------------------------------------------------
$checkB = $pdo->prepare('SELECT id FROM books WHERE ord = :ord');
$insB = $pdo->prepare(
    'INSERT INTO books (ord, osis, name, slug, aliases, testament, chapters)
     VALUES (:ord, :osis, :name, :slug, :aliases, :testament, :chapters)'
);
$updB = $pdo->prepare(
    'UPDATE books SET osis = :osis, name = :name, slug = :slug,
        aliases = :aliases, testament = :testament, chapters = :chapters
     WHERE ord = :ord'
);
$bCount = 0;
foreach ($books as $b) {
    $checkB->execute(['ord' => $b['ord']]);
    if ($checkB->fetch()) {
        $updB->execute($b);
        continue;
    }
    $insB->execute($b);
    $bCount++;
}
echo "Books: {$bCount} nuevos (" . count($books) . " en catálogo)\n";
echo "Seed done.\n";
