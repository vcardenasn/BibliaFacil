<?php

require __DIR__ . '/../bootstrap.php';

use Biblia\Bible\BibleRepository;
use Biblia\Bible\ReferenceParser;
use Biblia\Core\FeatureFlags;
use Biblia\Games\VerseQuiz;

$repo = new BibleRepository();
$versions = $repo->versions();
$path = trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$scriptDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
if ($scriptDir !== '' && str_starts_with($path, $scriptDir)) {
    $path = trim(substr($path, strlen($scriptDir)), '/');
}
$seg = $path === '' ? [] : explode('/', $path);

$notFound = function (string $msg = 'Página no encontrada.') use ($versions): void {
    http_response_code(404);
    view('notfound', ['title' => 'No encontrado', 'message' => $msg, 'versions' => $versions]);
};

// ---- /robots.txt — dinámico: el Sitemap toma el dominio actual (EPIC 18) ----
if (($seg[0] ?? '') === 'robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nAllow: /\n",
        "Disallow: /ir\nDisallow: /check.php\nDisallow: /track.php\n",
        "Disallow: /juegos/api/\nDisallow: /mias\n\n",
        'Sitemap: ', \Biblia\Core\Seo::abs('sitemap.xml'), "\n";
    exit;
}
if (($seg[0] ?? '') === 'sitemap.xml' || (($seg[0] ?? '') === 'sitemap' && isset($seg[1]))) {
    header('Content-Type: application/xml; charset=utf-8');
    $abs = fn (string $p) => \Biblia\Core\Seo::abs($p);
    if (($seg[0] ?? '') === 'sitemap.xml') {
        echo '<?xml version="1.0" encoding="UTF-8"?>', "\n",
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n",
            '<sitemap><loc>', $abs('sitemap/paginas'), '</loc></sitemap>', "\n";
        foreach ($versions as $v) {
            if (!empty($v['active'])) {
                echo '<sitemap><loc>', $abs('sitemap/' . $v['code']), '</loc></sitemap>', "\n";
            }
        }
        echo '</sitemapindex>';
        exit;
    }
    // sitemap de páginas estáticas o de una versión (capítulos)
    echo '<?xml version="1.0" encoding="UTF-8"?>', "\n",
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
    if ($seg[1] === 'paginas') {
        echo '<url><loc>', $abs(''), '</loc><priority>1.0</priority></url>', "\n",
            '<url><loc>', $abs('juegos'), '</loc><priority>0.7</priority></url>', "\n",
            '<url><loc>', $abs('temas'), '</loc><priority>0.9</priority></url>', "\n",
            '<url><loc>', $abs('versiculo-del-dia'), '</loc><priority>0.9</priority></url>', "\n",
            '<url><loc>', $abs('guias'), '</loc><priority>0.6</priority></url>', "\n";
        foreach (config('games') as $gslug => $g) {
            if (!empty($g['ready'])) {
                echo '<url><loc>', $abs('juegos/' . $gslug), '</loc><priority>0.5</priority></url>', "\n";
            }
        }
        foreach (config('temas') as $tslug => $t) {
            echo '<url><loc>', $abs('temas/' . $tslug), '</loc><priority>0.8</priority></url>', "\n";
        }
        foreach (config('versiculos') as $vslug => $ve) {
            echo '<url><loc>', $abs('versiculo/' . $vslug), '</loc><priority>0.7</priority></url>', "\n";
        }
        foreach (config('guias') as $gs2 => $g2) {
            echo '<url><loc>', $abs('guias/' . $gs2), '</loc><priority>0.5</priority></url>', "\n";
        }
    } else {
        $sv = $repo->versionByCode($seg[1]);
        if (!$sv) { http_response_code(404); echo '</urlset>'; exit; }
        echo '<url><loc>', $abs((string) $sv['code']), '</loc><priority>0.9</priority></url>', "\n";
        foreach ($repo->books() as $b) {
            echo '<url><loc>', $abs("{$sv['code']}/{$b['slug']}"), '</loc><priority>0.6</priority></url>', "\n";
            for ($c = 1; $c <= (int) $b['chapters']; $c++) {
                echo '<url><loc>', $abs("{$sv['code']}/{$b['slug']}/{$c}"), '</loc><priority>0.8</priority></url>', "\n";
            }
        }
    }
    echo '</urlset>';
    exit;
}

// ---- /ir — "ir a referencia" (Juan 3:16, salmos 23…) -------------------------
if (($seg[0] ?? '') === 'ir') {
    $q = (string) ($_GET['q'] ?? '');
    $v = (string) ($_GET['v'] ?? '');
    $version = $repo->versionByCode($v) ?: $versions[0] ?? null;
    $ref = (new ReferenceParser($repo->books()))->parse($q);
    if ($version && $ref) {
        $book = $repo->book($ref['osis']);
        if ($book && $ref['chapter'] >= 1 && $ref['chapter'] <= (int) $book['chapters']) {
            $anchor = $ref['verse'] ? '#v' . $ref['verse'] : '';
            \Biblia\Core\Metrics::bump('goto', $version['code']);
            header('Location: ' . url("{$version['code']}/{$book['slug']}/{$ref['chapter']}{$anchor}"));
            exit;
        }
    }
    $notFound('No entendí la referencia "' . $q . '". Ejemplos: "Juan 3:16", "Salmos 23", "1 Corintios 13".');
    exit;
}

// ---- /buscar — búsqueda de texto ---------------------------------------------
if (($seg[0] ?? '') === 'buscar') {
    FeatureFlags::requireEnabled('FF_SEARCH');
    $q = trim((string) ($_GET['q'] ?? ''));
    $v = (string) ($_GET['v'] ?? '');
    $version = $repo->versionByCode($v) ?: $versions[0] ?? null;
    $results = ($version && $q !== '') ? $repo->search((int) $version['id'], $q) : [];
    if ($q !== '') {
        \Biblia\Core\Metrics::bump('search_r', $results === [] ? 'empty' : 'hit');
    }
    view('search', [
        'title' => 'Buscar',
        'versions' => $versions,
        'version' => $version,
        'q' => $q,
        'results' => $results,
    ]);
    exit;
}

// ---- /temas — colecciones de versículos por tema (US-190) --------------------
if (($seg[0] ?? '') === 'temas') {
    $temas = config('temas');
    $tv = $repo->versionByCode((string) ($_GET['v'] ?? '')) ?: $repo->versionByCode((string) config('app.default_version', 'rvr1909')) ?: ($versions[0] ?? null);
    if (count($seg) === 1) {
        view('temas', ['title' => 'Versículos por tema', 'versions' => $versions, 'version' => $tv, 'temas' => $temas]);
        exit;
    }
    $slug = (string) $seg[1];
    if (!isset($temas[$slug]) || !$tv) {
        $notFound('Ese tema no existe.');
        exit;
    }
    view('tema', [
        'title' => 'Versículos de ' . $temas[$slug]['name'],
        'versions' => $versions, 'version' => $tv,
        'tema' => $temas[$slug], 'slug' => $slug,
        'verses' => $repo->versesByRefs((int) $tv['id'], $temas[$slug]['refs']),
    ]);
    exit;
}

// ---- /versiculo/{slug} — landing de versículo famoso (US-191) ----------------
if (($seg[0] ?? '') === 'versiculo' && isset($seg[1])) {
    $famosos = config('versiculos');
    $slug = (string) $seg[1];
    if (!isset($famosos[$slug])) {
        $notFound('Ese versículo aún no tiene página.');
        exit;
    }
    $e = $famosos[$slug];
    $texts = [];
    foreach ($versions as $v) {
        $row = $repo->verseByRef((int) $v['id'], $e['ref'][0], $e['ref'][1], $e['ref'][2]);
        if ($row) {
            $texts[] = $row + ['code' => $v['code'], 'name' => $v['name']];
        }
    }
    view('versiculo', [
        'title' => $e['title'] . ' — texto y significado',
        'versions' => $versions,
        'version' => $versions[0] ?? null,
        'entry' => $e, 'texts' => $texts, 'slug' => $slug,
    ]);
    exit;
}

// ---- /versiculo-del-dia — URL estable + archivo + RSS (US-193) ----------------
if (($seg[0] ?? '') === 'versiculo-del-dia') {
    $vv = $repo->versionByCode((string) ($_GET['v'] ?? '')) ?: $repo->versionByCode((string) config('app.default_version', 'rvr1909')) ?: ($versions[0] ?? null);
    if (($seg[1] ?? '') === 'rss') {
        header('Content-Type: application/rss+xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>', "\n",
            '<rss version="2.0"><channel><title>Versículo del día — Biblia Fácil</title>',
            '<link>', \Biblia\Core\Seo::abs('versiculo-del-dia'), '</link>',
            '<description>Un versículo de la Biblia cada día.</description><language>es</language>', "\n";
        for ($i = 0; $i < 30; $i++) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $item = $vv ? $repo->verseOfTheDay((int) $vv['id'], $d) : null;
            if ($item) {
                $url = \Biblia\Core\Seo::abs('versiculo-del-dia?d=' . $d);
                echo '<item><title>', e($item['book_name'] . ' ' . $item['chapter'] . ':' . $item['verse']),
                    '</title><link>', $url, '</link><guid isPermaLink="true">', $url, '</guid>',
                    '<pubDate>', date(DATE_RSS, strtotime($d . ' 06:00:00')), '</pubDate>',
                    '<description>', e(strip_tags((string) $item['text'])), '</description></item>', "\n";
            }
        }
        echo '</channel></rss>';
        exit;
    }
    $d = (string) ($_GET['d'] ?? '');
    $d = preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : date('Y-m-d');
    $archive = [];
    for ($i = 1; $i <= 14; $i++) {
        $ad = date('Y-m-d', strtotime("-{$i} days"));
        if ($ad !== $d) { $archive[] = $ad; }
    }
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    view('votd', [
        'title' => 'Versículo del día — ' . date('d/m/Y', strtotime($d)),
        'versions' => $versions, 'version' => $vv,
        'votd' => $vv ? $repo->verseOfTheDay((int) $vv['id'], $d) : null,
        'fechaTxt' => (int) date('j', strtotime($d)) . ' de ' . $meses[(int) date('n', strtotime($d)) - 1] . ' de ' . date('Y', strtotime($d)),
        'prev' => date('Y-m-d', strtotime($d . ' -1 day')),
        'next' => $d < date('Y-m-d') ? date('Y-m-d', strtotime($d . ' +1 day')) : null,
        'archive' => $archive,
        'd' => $d,
    ]);
    exit;
}

// ---- /guias — contenido editorial (US-194) -----------------------------------
if (($seg[0] ?? '') === 'guias') {
    $guias = config('guias');
    if (count($seg) === 1) {
        view('guias', ['title' => 'Guías', 'versions' => $versions, 'version' => $versions[0] ?? null, 'guias' => $guias]);
        exit;
    }
    $gslug = (string) $seg[1];
    if (!isset($guias[$gslug])) {
        $notFound('Esa guía no existe.');
        exit;
    }
    view('guia', [
        'title' => $guias[$gslug]['title'],
        'versions' => $versions, 'version' => $versions[0] ?? null,
        'guia' => $guias[$gslug],
    ]);
    exit;
}

// ---- /mias — anotaciones personales (IndexedDB del navegador) ----------------
if (($seg[0] ?? '') === 'mias') {
    view('mias', [
        'title' => 'Mis anotaciones',
        'versions' => $versions,
        'version' => $versions[0] ?? null,
    ]);
    exit;
}

// ---- /juegos — hub de juegos bíblicos ---------------------------------------
if (($seg[0] ?? '') === 'juegos') {
    // API: ronda de versículos para "Completa el Versículo" (solo lectura)
    if (($seg[1] ?? '') === 'api' && ($seg[2] ?? '') === 'versiculo') {
        header('Content-Type: application/json; charset=utf-8');
        $n = min(15, max(1, (int) ($_GET['n'] ?? 10)));
        $gv = $repo->versionByCode((string) ($_GET['v'] ?? '')) ?: ($versions[0] ?? null);
        try {
            $qs = $gv ? (new VerseQuiz())->round($repo, (int) $gv['id'], $n) : [];
            echo json_encode(['ok' => true, 'qs' => $qs], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false]);
        }
        exit;
    }
    $games = config('games');
    if (count($seg) === 1) {
        view('juegos', [
            'title' => 'Juegos Bíblicos',
            'versions' => $versions,
            'version' => $versions[0] ?? null,
            'games' => $games,
            'bodyClass' => 'jpage',
            'extraCss' => ['juegos.css'],
            'extraJs' => ['juegos.js'],
        ]);
        exit;
    }
    $slug = (string) $seg[1];
    if (!isset($games[$slug]) || empty($games[$slug]['ready'])) {
        $notFound('Ese juego aún no está listo.');
        exit;
    }
    view('juego', [
        'title' => $games[$slug]['name'],
        'versions' => $versions,
        'version' => $versions[0] ?? null,
        'game' => $games[$slug],
        'slug' => $slug,
        'bodyClass' => 'jpage',
        'extraCss' => ['juegos.css'],
        'extraJs' => ['juegos.js', 'juego-' . $slug . '.js'],
    ]);
    exit;
}

// ---- / — continuar donde quedó o capítulo por defecto ------------------------
if ($seg === []) {
    $default = config('app.default_version') . '/genesis/1';
    $pos = (string) ($_COOKIE['bf_pos'] ?? '');
    $target = preg_match('#^[a-z0-9\-]+/[a-z0-9\-]+/\d+$#', $pos) ? $pos : $default;
    header('Location: ' . url($target));
    exit;
}

$version = $repo->versionByCode($seg[0]);
if (!$version) {
    $notFound('Versión no disponible.');
    exit;
}

// ---- /{version} — índice de libros -------------------------------------------
if (count($seg) === 1) {
    $votd = $repo->verseOfTheDay((int) $version['id']);
    view('books', [
        'title' => $version['name'],
        'versions' => $versions,
        'version' => $version,
        'books' => $repo->books(),
        'votd' => $votd,
    ]);
    exit;
}

$book = $repo->book($seg[1]);
if (!$book) {
    $notFound('Libro no encontrado.');
    exit;
}

// ---- /{version}/{libro} — índice de capítulos --------------------------------
if (count($seg) === 2) {
    view('chapters', [
        'title' => "{$book['name']} — {$version['name']}",
        'versions' => $versions,
        'version' => $version,
        'book' => $book,
        'intro' => config('libros.' . $book['slug']),
    ]);
    exit;
}

// ---- /{version}/{libro}/{capítulo} — lector ----------------------------------
$chapter = ctype_digit($seg[2]) ? (int) $seg[2] : 0;
if (count($seg) === 3 && $chapter >= 1 && $chapter <= (int) $book['chapters']) {
    $verses = $repo->chapter((int) $version['id'], (int) $book['id'], $chapter);
    $nav = $repo->chapterNav($version, $book, $chapter);
    view('reader', [
        'title' => "{$book['name']} {$chapter} — {$version['name']}",
        'versions' => $versions,
        'version' => $version,
        'book' => $book,
        'chapter' => $chapter,
        'verses' => $verses,
        'nav' => $nav,
    ]);
    exit;
}

$notFound();
