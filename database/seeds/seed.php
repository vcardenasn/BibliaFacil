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
$hasApiBible = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
    ? (bool) $pdo->query("SELECT 1 FROM pragma_table_info('versions') WHERE name = 'api_bible_id'")->fetch()
    : (bool) $pdo->query("SHOW COLUMNS FROM versions LIKE 'api_bible_id'")->fetch();
$apiCols = $hasApiBible ? ', api_bible_id' : '';
$apiVals = $hasApiBible ? ', :api_bible_id' : '';
$apiSet = $hasApiBible ? ', api_bible_id = :api_bible_id' : '';

$insV = $pdo->prepare(
    "INSERT INTO versions (code, name, language, copyright, license, license_status, source_url{$apiCols}, active)
     VALUES (:code, :name, :language, :copyright, :license, :license_status, :source_url{$apiVals}, :active)"
);
$updV = $pdo->prepare(
    "UPDATE versions SET name = :name, language = :language, copyright = :copyright,
        license = :license, license_status = :license_status, source_url = :source_url{$apiSet}
     WHERE code = :code"
);
$vCount = 0;
foreach ($versions as $v) {
    $checkV->execute(['code' => $v['code']]);
    if ($checkV->fetch()) {
        // active no se sobreescribe: es decisión operativa del admin.
        $keys = ['name', 'language', 'copyright', 'license', 'license_status', 'source_url', 'code'];
        if ($hasApiBible) {
            $keys[] = 'api_bible_id';
        }
        $updV->execute(array_intersect_key($v + ['api_bible_id' => null], array_flip($keys)));
        continue;
    }
    $insV->execute($v + ['api_bible_id' => null]);
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
