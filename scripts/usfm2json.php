<?php

/**
 * Convierte un directorio USFM (eBible/DBL) al JSON normalizado del proyecto.
 *
 * Uso:  php scripts/usfm2json.php <dir-usfm> <code> [nombre]
 *   php scripts/usfm2json.php /tmp/spaonbv onbv "Biblica® Open NBV 2008"
 *
 * Escribe database/sources/<code>.json con sentinels [wj]…[/wj] en el texto.
 */

require __DIR__ . '/../bootstrap.php';

use Biblia\Bible\UsfmParser;

[$dir, $code] = [$argv[1] ?? null, $argv[2] ?? null];
if (!$dir || !$code || !is_dir($dir)) {
    fwrite(STDERR, "Uso: php scripts/usfm2json.php <dir-usfm> <code> [nombre]\n");
    exit(1);
}

$booksCatalog = require CONFIG_PATH . '/books.php';
$osisToOrd = array_column($booksCatalog, 'ord', 'osis');

$parser = new UsfmParser($osisToOrd);
$books = $parser->parseDir($dir);
if (count($books) < 66) {
    fwrite(STDERR, 'Solo ' . count($books) . " libros reconocidos (se esperaban 66)\n");
    exit(1);
}

$verses = 0;
$wjVerses = 0;
foreach ($books as &$b) {
    $b['chapters'] = array_values(array_map('array_values', $b['chapters']));
    foreach ($b['chapters'] as $ch) {
        foreach ($ch as $v) {
            $verses++;
            if (str_contains($v['t'], '[wj]')) {
                $wjVerses++;
            }
        }
    }
}
unset($b);

$out = [
    'name' => $argv[3] ?? $code,
    'abbreviation' => $code,
    'language' => 'es',
    'format' => 'usfm-normalized',
    'books' => array_map(fn ($b) => [
        'osis' => $b['osis'],
        'name' => $booksCatalog[$b['ord'] - 1]['name'] ?? $b['osis'],
        'chapters' => $b['chapters'],
    ], $books),
];

$target = BASE_PATH . "/database/sources/{$code}.json";
file_put_contents($target, json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "{$target}: " . count($books) . " libros, " . number_format($verses)
    . " versículos (" . number_format($wjVerses) . " con palabras de Jesús), "
    . number_format(filesize($target) / 1048576, 1) . " MB\n";
