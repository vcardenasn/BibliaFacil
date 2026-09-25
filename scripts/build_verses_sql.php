<?php

/**
 * Genera database/verses_<code>.sql (MySQL, importable por phpMyAdmin)
 * desde una fuente JSON en database/sources/.
 *
 * Uso:  php scripts/build_verses_sql.php database/sources/onbv.json onbv
 *
 * - FKs por subquery (@vid/@bid), INSERT IGNORE (idempotente), batch de 500.
 * - Incluye upsert de la fila en `versions` (la tabla prod ya está seedeada).
 * - Requiere la columna verses.wj → correr antes database/upgrade_verses_wj.sql.
 * - NO depende de la BD — solo lee el JSON.
 */

require __DIR__ . '/../bootstrap.php';

use Biblia\Bible\VerseText;

$file = $argv[1] ?? null;
$code = $argv[2] ?? null;
if (!$file || !$code || !is_file($file)) {
    fwrite(STDERR, "Uso: php scripts/build_verses_sql.php <fuente.json> <code>\n");
    exit(1);
}

$data = json_decode((string) file_get_contents($file), true);
if (!is_array($data) || empty($data['books'])) {
    fwrite(STDERR, "Formato no reconocido en {$file}\n");
    exit(1);
}
$books = array_values($data['books']);
if (count($books) < 66) {
    fwrite(STDERR, 'Fuente incompleta: ' . count($books) . " libros (<66)\n");
    exit(1);
}

$booksCatalog = require CONFIG_PATH . '/books.php';
$versionsCatalog = array_column(require CONFIG_PATH . '/versions.php', null, 'code');
$meta = $versionsCatalog[$code]
    ?? ['name' => $code, 'language' => 'es', 'copyright' => null, 'license' => 'unknown', 'license_status' => 'open', 'source_url' => null, 'active' => 1];

$esc = fn (?string $s): string => $s === null ? 'NULL' : "'" . str_replace(["\\", "'"], ["\\\\", "''"], $s) . "'";

$out = "-- BibliaFacil — versículos {$code} (importar por phpMyAdmin tras schema.sql\n";
$out .= "-- y upgrade_verses_wj.sql). Idempotente: re-importar no duplica.\n\n";
$out .= "SET NAMES utf8mb4;\n\n";

// La versión puede no existir aún en la tabla (seed anterior no la conocía).
$out .= "-- Seed idempotente de la versión\nINSERT IGNORE INTO versions\n";
$out .= "(code, name, language, copyright, license, license_status, source_url, active) VALUES\n";
$out .= '(' . implode(', ', [
    $esc($code), $esc($meta['name']), $esc($meta['language']), $esc($meta['copyright']),
    $esc($meta['license']), $esc($meta['license_status']), $esc($meta['source_url']), (int) ($meta['active'] ?? 1),
]) . ");\n\n";

$out .= "SET @vid := (SELECT id FROM versions WHERE code = {$esc($code)});\n\n";

$total = 0;
$wjTotal = 0;
foreach (array_slice($books, 0, 66) as $i => $book) {
    $osis = $booksCatalog[$i]['osis'];
    $rows = [];
    foreach ($book['chapters'] as $ci => $ch) {
        $verses = isset($ch['verses']) ? $ch['verses'] : $ch;
        $chapter = isset($ch['chapter']) ? (int) $ch['chapter'] : $ci + 1;
        foreach ($verses as $vi => $v) {
            $raw = is_array($v) ? (string) ($v['t'] ?? $v['text'] ?? '') : (string) $v;
            [$text, $wj] = VerseText::split($raw);
            $verse = is_array($v) ? (int) ($v['v'] ?? $v['verse'] ?? $vi + 1) : $vi + 1;
            if ($text === '') {
                continue;
            }
            if ($wj !== null) {
                $wjTotal++;
            }
            $rows[] = "(@vid,@bid,{$chapter},{$verse}," . $esc($text) . ',' . $esc($wj) . ')';
        }
    }
    if (!$rows) {
        continue;
    }
    $out .= "SET @bid := (SELECT id FROM books WHERE osis = '{$osis}');\n";
    foreach (array_chunk($rows, 500) as $chunk) {
        $out .= "INSERT IGNORE INTO verses (version_id, book_id, chapter, verse, text, wj) VALUES\n"
            . implode(",\n", $chunk) . ";\n";
        $total += count($chunk);
    }
}

$target = BASE_PATH . "/database/verses_{$code}.sql";
file_put_contents($target, $out);
echo "{$target}: " . number_format($total) . " versículos ({$wjTotal} con wj), "
    . number_format(strlen($out) / 1048576, 1) . " MB\n";
