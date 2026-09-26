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
    view('search', [
        'title' => 'Buscar',
        'versions' => $versions,
        'version' => $version,
        'q' => $q,
        'results' => $results,
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
