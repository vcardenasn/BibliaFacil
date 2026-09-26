<?php

require __DIR__ . '/../bootstrap.php';

use Biblia\Bible\BibleRepository;
use Biblia\Bible\ReadingPlan;
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
        "Disallow: /juegos/api/\nDisallow: /api/\nDisallow: /comparar\nDisallow: /mias\n\n",
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
            '<url><loc>', $abs('guias'), '</loc><priority>0.6</priority></url>', "\n",
            '<url><loc>', $abs('planes'), '</loc><priority>0.7</priority></url>', "\n";
        foreach (config('plans') as $pslug => $p) {
            echo '<url><loc>', $abs('planes/' . $pslug), '</loc><priority>0.6</priority></url>', "\n";
        }
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

// ---- /img/{v}/{libro}/{cap}/{ver} — og:image del versículo (US-200) ----------
if (($seg[0] ?? '') === 'img' && count($seg) === 5) {
    $iv = $repo->versionByCode($seg[1]);
    $ib = $iv ? $repo->book($seg[2]) : null;
    $ivv = $ib ? $repo->verseByRef((int) $iv['id'], (string) $ib['osis'], (int) $seg[3], (int) $seg[4]) : null;
    $png = $ivv ? \Biblia\Core\VerseImage::png(
        strip_tags((string) $ivv['text']),
        $ivv['book_name'] . ' ' . $ivv['chapter'] . ':' . $ivv['verse'],
        "{$seg[1]}-{$seg[2]}-{$seg[3]}-{$seg[4]}"
    ) : null;
    if (!$png) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=604800'); // 7 días — el contenido no cambia
    echo $png;
    exit;
}

// ---- /v/{libro}/{cap}/{ver} — URL corta compartible (US-201) -----------------
if (($seg[0] ?? '') === 'v' && count($seg) === 4) {
    $sv = $repo->versionByCode((string) ($_GET['v'] ?? ''))
        ?: $repo->versionByCode((string) config('app.default_version', 'rvr1909'))
        ?: ($versions[0] ?? null);
    $sb = $repo->book($seg[1]);
    $svv = ($sv && $sb) ? $repo->verseByRef((int) $sv['id'], (string) $sb['osis'], (int) $seg[2], (int) $seg[3]) : null;
    if (!$svv) {
        $notFound('Ese versículo no existe.');
        exit;
    }
    $ref = $svv['book_name'] . ' ' . $svv['chapter'] . ':' . $svv['verse'];
    view('shareverse', [
        'title' => $ref . ' — ' . $sv['name'],
        'versions' => $versions, 'version' => $sv,
        'verse' => $svv, 'ref' => $ref,
        'shareUrl' => \Biblia\Core\Seo::abs("v/{$seg[1]}/{$seg[2]}/{$seg[3]}?v={$sv['code']}"),
        'imgUrl' => \Biblia\Core\Seo::abs("img/{$sv['code']}/{$seg[1]}/{$seg[2]}/{$seg[3]}"),
        'chapterUrl' => url("{$sv['code']}/{$seg[1]}/{$seg[2]}#v{$seg[3]}"),
    ]);
    exit;
}

// ---- /api/contexto — ±3 versículos para expandir resultados (US-142) ---------
if (($seg[0] ?? '') === 'api' && ($seg[1] ?? '') === 'contexto') {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Robots-Tag: noindex');
    $cv = $repo->versionByCode((string) ($_GET['v'] ?? ''));
    $cb = $cv ? $repo->book((string) ($_GET['b'] ?? '')) : null;
    $cc = (int) ($_GET['c'] ?? 0);
    $cvv = (int) ($_GET['n'] ?? 0);
    $rows = ($cb && $cc >= 1 && $cc <= (int) $cb['chapters'] && $cvv >= 1)
        ? $repo->chapter((int) $cv['id'], (int) $cb['id'], $cc)
        : [];
    $ctx = [];
    foreach ($rows as $r) {
        if (abs((int) $r['verse'] - $cvv) <= 3) {
            $ctx[] = ['v' => (int) $r['verse'], 't' => $r['text']];
        }
    }
    echo json_encode(['ok' => $ctx !== [], 'ref' => $cb ? $cb['name'] . ' ' . $cc : '', 'verses' => $ctx], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- /comparar — dos versiones lado a lado (US-140/US-050) -------------------
if (($seg[0] ?? '') === 'comparar') {
    // El formulario GET normaliza a la URL canónica /comparar/{libro}/{cap}/{a}/{b}
    if (isset($_GET['book'])) {
        $fb = $repo->book((string) $_GET['book']);
        $fa = $repo->versionByCode((string) ($_GET['a'] ?? ''));
        $fbv = $repo->versionByCode((string) ($_GET['b'] ?? ''));
        $fc = max(1, (int) ($_GET['cap'] ?? 1));
        if (!$fb || !$fa || !$fbv || $fc > (int) $fb['chapters']) {
            $notFound('No pude armar esa comparación.');
            exit;
        }
        if ($fa['id'] === $fbv['id']) {
            $notFound('Elige dos versiones distintas para comparar.');
            exit;
        }
        header('Location: ' . url("comparar/{$fb['slug']}/{$fc}/{$fa['code']}/{$fbv['code']}"), true, 302);
        exit;
    }
    $cbook = isset($seg[1]) ? $repo->book((string) $seg[1]) : null;
    if (!$cbook) {
        // Picker: elegir libro/capítulo/versiones sin JS (GET → redirect canónico)
        view('comparar', [
            'title' => 'Comparar versiones',
            'versions' => $versions,
            'version' => $versions[0] ?? null,
            'books' => $repo->books(),
            'book' => null, 'chapter' => 1,
            'va' => null, 'vb' => null,
            'versesA' => [], 'versesB' => [], 'nav' => null,
        ]);
        exit;
    }
    $cch = ctype_digit($seg[2] ?? '') ? (int) $seg[2] : 0;
    if ($cch < 1 || $cch > (int) $cbook['chapters']) {
        $notFound('Ese capítulo no existe.');
        exit;
    }
    $def = (string) config('app.default_version', 'rvr1909');
    $va = isset($seg[3]) ? $repo->versionByCode((string) $seg[3]) : ($repo->versionByCode($def) ?: ($versions[0] ?? null));
    $vb = isset($seg[4]) ? $repo->versionByCode((string) $seg[4]) : null;
    if (!$vb || ($va && $vb['id'] === $va['id'])) {
        $vb = null;
        foreach ($versions as $v2) {
            if ($va && $v2['id'] !== $va['id'] && $v2['code'] === 'onbv') { $vb = $v2; break; }
        }
        if (!$vb) {
            foreach ($versions as $v2) {
                if ($va && $v2['id'] !== $va['id']) { $vb = $v2; break; }
            }
        }
    }
    if (!$va || !$vb) {
        $notFound('Necesito dos versiones cargadas para comparar.');
        exit;
    }
    \Biblia\Core\Metrics::bump('cmp', $va['code'] . '>' . $vb['code']);
    $navA = $repo->chapterNav($va, $cbook, $cch);
    $cmpNav = ['prev' => null, 'next' => null];
    foreach (['prev', 'next'] as $dir) {
        if ($navA[$dir]) {
            $np = explode('/', ltrim($navA[$dir], '/'));
            $cmpNav[$dir] = "comparar/{$np[1]}/{$np[2]}/{$va['code']}/{$vb['code']}";
        }
    }
    view('comparar', [
        'title' => "Comparar {$cbook['name']} {$cch}: " . strtoupper($va['code']) . ' vs ' . strtoupper($vb['code']),
        'versions' => $versions,
        'version' => $va,
        'books' => $repo->books(),
        'book' => $cbook, 'chapter' => $cch,
        'va' => $va, 'vb' => $vb,
        'versesA' => array_column($repo->chapter((int) $va['id'], (int) $cbook['id'], $cch), null, 'verse'),
        'versesB' => array_column($repo->chapter((int) $vb['id'], (int) $cbook['id'], $cch), null, 'verse'),
        'nav' => $cmpNav,
    ]);
    exit;
}

// ---- /planes — planes de lectura (EPIC 05) -----------------------------------
if (($seg[0] ?? '') === 'planes') {
    $plans = config('plans');
    $booksAll = $repo->books();
    // Abre los capítulos en tu última versión usada (cookie), si no, la por defecto
    $pvCode = (string) config('app.default_version', 'rvr1909');
    if (preg_match('#^([a-z0-9-]+)/#', (string) ($_COOKIE['bf_pos'] ?? ''), $m)) { $pvCode = $m[1]; }
    $planVersion = $repo->versionByCode($pvCode)
        ?: $repo->versionByCode((string) config('app.default_version', 'rvr1909'))
        ?: ($versions[0] ?? null);
    if (count($seg) === 1) {
        $totals = [];
        foreach ($plans as $ps => $p) { $totals[$ps] = count(ReadingPlan::readings($p, $booksAll)); }
        view('planes', [
            'title' => 'Planes de lectura de la Biblia',
            'versions' => $versions,
            'version' => $planVersion,
            'plans' => $plans,
            'planTotals' => $totals,
        ]);
        exit;
    }
    $slug = (string) ($seg[1] ?? '');
    if (!isset($plans[$slug])) {
        $notFound('Plan no encontrado.');
        exit;
    }
    view('plan', [
        'title' => $plans[$slug]['name'] . ' — plan de lectura',
        'versions' => $versions,
        'version' => $planVersion,
        'plan' => $plans[$slug],
        'planVersion' => $planVersion,
        'slug' => $slug,
        'days' => ReadingPlan::days($plans[$slug], $booksAll),
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

// ---- / — portada y continuación de lectura -----------------------------------
if ($seg === []) {
    $homeVersion = $repo->versionByCode((string) config('app.default_version', 'rvr1909')) ?: ($versions[0] ?? null);
    $startVersion = $repo->versionByCode('onbv') ?: $homeVersion;
    $continue = null;
    $pos = (string) ($_COOKIE['bf_pos'] ?? '');
    if (preg_match('#^([a-z0-9-]+)/([a-z0-9-]+)/([1-9][0-9]{0,2})$#', $pos, $matches)) {
        $savedVersion = $repo->versionByCode($matches[1]);
        $savedBook = $repo->book($matches[2]);
        if ($savedVersion && $savedBook && (int) $matches[3] <= (int) $savedBook['chapters']) {
            $continue = ['path' => $pos, 'label' => $savedBook['name'] . ' ' . $matches[3], 'version' => $savedVersion['name']];
        }
    }
    view('home', [
        'title' => 'Lee la Biblia en línea, gratis y sin anuncios',
        'versions' => $versions,
        'bodyClass' => 'home-page',
        'votdVersion' => $homeVersion,
        'startVersion' => $startVersion,
        'votd' => $homeVersion ? $repo->verseOfTheDay((int) $homeVersion['id']) : null,
        'continue' => $continue,
        'featuredThemes' => array_intersect_key(config('temas'), array_flip(['amor', 'animo', 'paz', 'familia'])),
        'featuredGames' => array_slice(config('games'), 0, 3, true),
    ]);
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
    // US-115/US-140: propone otra versión para comparar (prefiere ONBV)
    $otherV = null;
    foreach ($versions as $v2) {
        if ($v2['id'] !== $version['id'] && $v2['code'] === 'onbv') { $otherV = $v2; break; }
    }
    if (!$otherV) {
        foreach ($versions as $v2) {
            if ($v2['id'] !== $version['id']) { $otherV = $v2; break; }
        }
    }
    view('reader', [
        'title' => "{$book['name']} {$chapter} — {$version['name']}",
        'versions' => $versions,
        'version' => $version,
        'book' => $book,
        'chapter' => $chapter,
        'verses' => $verses,
        'nav' => $nav,
        'cmpUrl' => $otherV ? "comparar/{$book['slug']}/{$chapter}/{$version['code']}/{$otherV['code']}" : null,
    ]);
    exit;
}

$notFound();
