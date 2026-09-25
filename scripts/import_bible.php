<?php

/**
 * EPIC 02 / US-010 — importa una versión completa a la BD.
 *
 * Uso:
 *   php scripts/import_bible.php --file=database/sources/rvr1909.json --code=rvr1909
 *
 * Formatos soportados:
 *   - scrollmapper JSON: {books:[{chapters:[{chapter,verses:[{verse,text}]}]}]}
 *   - normalizado: {books:[{name,chapters:[[v1,v2,...]]}]}
 *
 * En hosting sin SSH: correr como Cron Job one-shot de cPanel o localmente
 * contra el MySQL remoto (habilitar Remote MySQL con tu IP).
 */

require __DIR__ . '/../bootstrap.php';

use Biblia\Bible\BibleImporter;
use Biblia\Core\Database;

$options = getopt('', ['file:', 'code:']);
$file = $options['file'] ?? null;
$code = $options['code'] ?? null;

if (!$file || !$code) {
    fwrite(STDERR, "Uso: php scripts/import_bible.php --file=<ruta.json> --code=<version>\n");
    exit(1);
}

$stats = (new BibleImporter(Database::getPdo()))->importFile($file, $code);

echo sprintf(
    "OK %s: %d libros, %d versículos insertados, %d ya existían.\n",
    $stats['version'],
    $stats['books'],
    $stats['inserted'],
    $stats['skipped']
);
