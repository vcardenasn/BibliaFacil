<?php
/**
 * Descarga ilustraciones "Bible Illustrations by Sweet Media" desde Wikimedia
 * Commons (CC BY-SA 3.0, https://freebibleimages.org) como miniaturas de 480px.
 *
 * Uso: php scripts/fetch_bibleimg.php
 *
 * Mantiene docs/bibleimg-manifest.json con el título exacto de cada archivo
 * descargado (necesario para la atribución CC BY-SA).
 */

const OUT = __DIR__ . '/../public/assets/bibleimg';
const MANIFEST = __DIR__ . '/../docs/bibleimg-manifest.json';

// slug => [término de búsqueda en Commons, nº de imágenes]
$MAP = [
    // --- Portadas de juegos -------------------------------------------------
    'juego-vf'        => ['Thomas doubts', 1],
    'juego-trivia'    => ['Solomon', 1],
    'juego-versiculo' => ['Isaiah', 1],
    'juego-historia'  => ['creation Sweet Media', 1],
    'juego-memory'    => ['Noah', 1],
    'juego-libros'    => ['Nehemiah', 1],
    'juego-personaje' => ['Samuel', 1],

    // --- Memory: dos fotogramas distintos por historia ----------------------
    'jonas'      => ['Jonah Sweet Media', 2],
    'daniel'     => ['Daniel lions den Sweet Media', 2],
    'david'      => ['David', 2],
    'moises'     => ['Moses burning bush Sweet Media', 2],
    'nacimiento' => ['birth Jesus nativity Sweet Media', 2],
    'panes'      => ['Matthew', 2],
    'jerico'     => ['Joshua', 2],
    'jacob'      => ['Jacob', 2],
    'sanson'     => ['Samson Sweet Media', 2],
    'ester'      => ['Esther', 2],
    'jesusagua'  => ['storm sea', 2],

    // --- Personajes (adivina quién) -----------------------------------------
    'p-jonas'    => ['Jonah', 1],
    'p-moises'   => ['Moses', 1],
    'p-david'    => ['David Jonathan', 1],
    'p-daniel'   => ['Daniel', 1],
    'p-jose'     => ['Joseph', 1],
    'p-sanson'   => ['Samson', 1],
    'p-pedro'    => ['Peter apostle', 1],
    'p-ester'    => ['Esther', 1],
    'p-abraham'  => ['Abraham', 1],
    'p-eva'      => ['Adam Eve', 1],
    'p-pablo'    => ['Paul Damascus Sweet Media', 1],
    'p-rut'      => ['Ruth Boaz', 1],
    'p-elias'    => ['Elijah ravens Sweet Media', 1],
    'p-josue'    => ['Joshua', 1],
    'p-lazaro'   => ['Martha', 1],
    'p-zaqueo'   => ['Zacchaeus', 1],
    'p-maria'    => ['Mary', 1],
    'p-gedeon'   => ['Gideon', 1],
    'p-samuel'   => ['Samuel', 1],
    'p-balaam'   => ['Numbers', 1],
    'p-jacob'    => ['Jacob Esau Sweet Media', 1],
    'p-nehemias' => ['Nehemiah', 1],
    'p-esdras'   => ['Ezra', 1],
    'p-mariamag' => ['Mary Magdalene resurrection Sweet Media', 1],
    'p-tomas'    => ['doubting Thomas Sweet Media', 1],
    'p-juan'     => ['Jordan', 1],
    'p-cain'     => ['Cain Abel Sweet Media', 1],
    'p-eliseo'   => ['Elisha Sweet Media', 1],
    'p-ana'      => ['Hannah', 1],
];

// slug => [títulos exactos en Commons] — para búsquedas sin resultados
// (los títulos de Sweet Media no contienen nombres propios, solo capítulos).
$DIRECT = [
    'juego-memory' => ['Book of Genesis Chapter 7-2 (Bible Illustrations by Sweet Media).jpg'],
    'jesusagua'    => [
        'Gospel of Matthew Chapter 14-4 (Bible Illustrations by Sweet Media).jpg',
        'Gospel of Matthew Chapter 14-2 (Bible Illustrations by Sweet Media).jpg',
    ],
    'p-lazaro'     => ['Gospel of John Chapter 11-3 (Bible Illustrations by Sweet Media).jpg'],
    'p-gedeon'     => ['Book of Judges Chapter 7-1 (Bible Illustrations by Sweet Media).jpg'],
];

function api(array $params): array
{
    $url = 'https://commons.wikimedia.org/w/api.php?' . http_build_query($params + ['format' => 'json']);
    $ctx = stream_context_create(['http' => [
        'user_agent' => 'BibliaFacil/1.0 (asset fetcher; contact: admin)',
        'timeout' => 30,
    ]]);
    for ($try = 0; $try < 5; $try++) {
        usleep(1200000); // ~1 req/seg: respetar rate limit de Wikimedia
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw !== false && ($d = json_decode($raw, true))) {
            return $d;
        }
        sleep(4 * ($try + 1)); // backoff ante 429/errores
    }
    return [];
}

$manifest = [];
$fail = [];

foreach ($MAP as $slug => [$term, $n]) {
    $res = api([
        'action' => 'query', 'list' => 'search',
        'srsearch' => 'Sweet Media ' . $term, // sin "Sweet Media" los top hits no son del set
        'srnamespace' => 6, 'srlimit' => 12,
    ]);
    $files = [];
    foreach ($res['query']['search'] ?? [] as $r) {
        if (str_contains($r['title'], 'Sweet Media') && str_ends_with(strtolower($r['title']), '.jpg')) {
            $files[] = $r['title'];
        }
        if (count($files) >= $n) {
            break;
        }
    }
    if (!$files) {
        $fail[] = "$slug (sin resultados: $term)";
        continue;
    }
    foreach ($files as $k => $title) {
        $ii = api([
            'action' => 'query', 'prop' => 'imageinfo',
            'iiprop' => 'url', 'iiurlwidth' => 480,
            'titles' => $title,
        ]);
        $thumb = null;
        foreach ($ii['query']['pages'] ?? [] as $page) {
            $thumb = $page['imageinfo'][0]['thumburl'] ?? null;
        }
        if (!$thumb) {
            $fail[] = "$slug ($title sin thumb)";
            continue;
        }
        $suffix = $n > 1 ? '-' . chr(97 + $k) : '';
        $dest = OUT . "/{$slug}{$suffix}.jpg";
        if (is_file($dest)) { // idempotente: no re-descargar
            $manifest["{$slug}{$suffix}"] = $title;
            echo "SKIP {$slug}{$suffix}.jpg\n";
            continue;
        }
        $img = @file_get_contents($thumb, false, stream_context_create([
            'http' => ['user_agent' => 'BibliaFacil/1.0', 'timeout' => 30],
        ]));
        if ($img === false) {
            $fail[] = "$slug (descarga falló: $title)";
            continue;
        }
        file_put_contents($dest, $img);
        $manifest["{$slug}{$suffix}"] = $title;
        echo "OK  {$slug}{$suffix}.jpg ← {$title}\n";
    }
}

// Segunda pasada: títulos exactos, sin buscador
foreach ($DIRECT as $slug => $titles) {
    foreach ($titles as $k => $base) {
        $suffix = count($titles) > 1 ? '-' . chr(97 + $k) : '';
        $dest = OUT . "/{$slug}{$suffix}.jpg";
        if (is_file($dest)) {
            continue;
        }
        $ii = api([
            'action' => 'query', 'prop' => 'imageinfo',
            'iiprop' => 'url', 'iiurlwidth' => 480,
            'titles' => 'File:' . $base,
        ]);
        $thumb = null;
        foreach ($ii['query']['pages'] ?? [] as $page) {
            $thumb = $page['imageinfo'][0]['thumburl'] ?? null;
        }
        if (!$thumb) {
            echo "MISS {$slug}{$suffix} ← $base\n";
            continue;
        }
        $img = @file_get_contents($thumb, false, stream_context_create([
            'http' => ['user_agent' => 'BibliaFacil/1.0', 'timeout' => 30],
        ]));
        if ($img === false) {
            continue;
        }
        file_put_contents($dest, $img);
        $manifest["{$slug}{$suffix}"] = 'File:' . $base;
        echo "OK  {$slug}{$suffix}.jpg ← $base\n";
    }
}

file_put_contents(MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "\n" . count($manifest) . " imágenes. Fallos:\n";
foreach ($fail as $f) {
    echo "  - $f\n";
}
