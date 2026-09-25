<?php

/**
 * Genera database/verses_<code>.sql (MySQL, importable por phpMyAdmin)
 * desde una fuente JSON en database/sources/.
 *
 * Uso:  php scripts/build_verses_sql.php database/sources/rvr1909.json rvr1909
 *
 * Convenciones: FKs por subquery (@vid/@bid), INSERT IGNORE (idempotente),
 * batch de 500 filas por statement. NO depende de la BD — solo lee el JSON.
 */

require __DIR__ . '/../bootstrap.php';

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
$esc = fn (string $s): string => "'" . str_replace(["\\", "'"], ["\\\\", "''"], $s) . "'";
$clean = function (string $t): string {
    $t = strip_tags($t);
    return trim((string) preg_replace('/\s+/u', ' ', $t));
};

$out = "-- BibliaFacil — versículos {$code} (importar por phpMyAdmin tras schema.sql)\n";
$out .= "-- Requiere: versions.code='{$code}' y books cargados (schema.sql ya los crea)\n\n";
$out .= "SET NAMES utf8mb4;\n";
$out .= "SET @vid := (SELECT id FROM versions WHERE code = {$esc($code)});\n\n";

$total = 0;
foreach (array_slice($books, 0, 66) as $i => $book) {
    $osis = $booksCatalog[$i]['osis'];
    $rows = [];
    foreach ($book['chapters'] as $ci => $ch) {
        $verses = isset($ch['verses']) ? $ch['verses'] : $ch;
        $chapter = isset($ch['chapter']) ? (int) $ch['chapter'] : $ci + 1;
        foreach ($verses as $vi => $v) {
            $text = $clean(is_array($v) ? (string) ($v['text'] ?? '') : (string) $v);
            $verse = is_array($v) && isset($v['verse']) ? (int) $v['verse'] : $vi + 1;
            if ($text === '') {
                continue;
            }
            $rows[] = "(@vid,@bid,{$chapter},{$verse}," . $esc($text) . ')';
        }
    }
    if (!$rows) {
        continue;
    }
    $out .= "SET @bid := (SELECT id FROM books WHERE osis = '{$osis}');\n";
    foreach (array_chunk($rows, 500) as $chunk) {
        $out .= "INSERT IGNORE INTO verses (version_id, book_id, chapter, verse, text) VALUES\n"
            . implode(",\n", $chunk) . ";\n";
        $total += count($chunk);
    }
}

$target = BASE_PATH . "/database/verses_{$code}.sql";
file_put_contents($target, $out);
echo "{$target}: " . number_format($total) . " versículos, " . number_format(strlen($out) / 1048576, 1) . " MB\n";
