<!DOCTYPE html>
<html lang="<?= e($meta['htmlLang'] ?? 'es') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Biblia Fácil') ?> · Biblia Fácil</title>
<meta name="description" content="<?= e($meta['desc'] ?? 'Lee la Biblia en múltiples versiones, fácil y rápido.') ?>">
<?php if (!empty($meta['noindex'])): ?>
<meta name="robots" content="noindex,follow">
<?php endif; ?>
<link rel="canonical" href="<?= e($meta['canonical'] ?? '') ?>">
<meta property="og:type" content="<?= e($meta['ogType'] ?? 'website') ?>">
<meta property="og:title" content="<?= e(($title ?? 'Biblia Fácil') . ' · Biblia Fácil') ?>">
<meta property="og:description" content="<?= e($meta['desc'] ?? '') ?>">
<meta property="og:url" content="<?= e($meta['canonical'] ?? '') ?>">
<meta property="og:site_name" content="Biblia Fácil">
<meta property="og:locale" content="<?= e($meta['locale'] ?? 'es_LA') ?>">
<?php if (!empty($meta['image'])): ?>
<meta property="og:image" content="<?= e($meta['image']) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= e($meta['image']) ?>">
<?php else: ?>
<meta name="twitter:card" content="summary">
<?php endif; ?>
<meta name="twitter:title" content="<?= e(($title ?? 'Biblia Fácil') . ' · Biblia Fácil') ?>">
<meta name="twitter:description" content="<?= e($meta['desc'] ?? '') ?>">
<?php foreach (($meta['hreflang'] ?? []) as $lang => $u): ?>
<link rel="alternate" hreflang="<?= e($lang) ?>" href="<?= e($u) ?>">
<?php endforeach; ?>
<meta name="theme-color" content="#2e4a8a">
<link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
<?php foreach (($extraCss ?? []) as $c): ?>
<link rel="stylesheet" href="<?= e(asset($c)) ?>">
<?php endforeach; ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📖</text></svg>">
<?php foreach (($meta['jsonld'] ?? []) as $block): ?>
<script type="application/ld+json"><?= json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php endforeach; ?>
</head>
<body<?= !empty($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?>>
<a class="skip-link" href="#main-content">Saltar al contenido</a>
<header class="topbar">
    <a class="brand" href="<?= e(url('/')) ?>"><span class="cross" aria-hidden="true">✝</span> Biblia Fácil</a>
    <?php
    $activeSection = explode('/', trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/'))[0];
    $defaultRead = (string) config('app.default_version', 'rvr1909');
    $readCode = in_array($defaultRead, array_column($versions ?? [], 'code'), true) ? $defaultRead : ($versions[0]['code'] ?? 'rvr1909');
    ?>
    <nav class="primary-nav" aria-label="Navegación principal">
        <a href="<?= e(url('/')) ?>"<?= $activeSection === '' ? ' aria-current="page"' : '' ?>><span aria-hidden="true">⌂</span><span>Inicio</span></a>
        <a href="<?= e(url($readCode)) ?>"<?= in_array($activeSection, array_column($versions ?? [], 'code'), true) ? ' aria-current="true"' : '' ?>><span aria-hidden="true">▤</span><span>Leer</span></a>
        <a href="<?= e(url('planes')) ?>"<?= $activeSection === 'planes' ? ' aria-current="page"' : '' ?>><span aria-hidden="true">▦</span><span>Planes</span></a>
        <a href="<?= e(url('temas')) ?>"<?= in_array($activeSection, ['temas', 'guias', 'versiculo', 'versiculo-del-dia', 'v'], true) ? ' aria-current="true"' : '' ?>><span aria-hidden="true">◇</span><span>Explorar</span></a>
        <a href="<?= e(url('juegos')) ?>"<?= $activeSection === 'juegos' ? ' aria-current="true"' : '' ?>><span aria-hidden="true">✦</span><span>Juegos</span></a>
        <a href="<?= e(url('mias')) ?>"<?= $activeSection === 'mias' ? ' aria-current="page"' : '' ?>><span aria-hidden="true">♡</span><span>Mis notas</span></a>
    </nav>
    <?php if (!empty($versions) && !empty($version)): ?>
    <select class="vswitch" id="versionSwitch" data-version="<?= e($version['code']) ?>" aria-label="Cambiar versión" title="Cambiar versión">
        <?php foreach ($versions as $v): ?>
        <option value="<?= e($v['code']) ?>"<?= $v['id'] === $version['id'] ? ' selected' : '' ?>><?= e($v['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <form class="goto" method="get" action="<?= e(url('ir')) ?>" role="search">
        <?php if (!empty($version)): ?>
        <input type="hidden" name="v" value="<?= e($version['code']) ?>">
        <?php endif; ?>
        <label class="sr-only" for="quick-reference">Ir a una referencia bíblica</label>
        <input id="quick-reference" type="text" name="q" placeholder="Ir a: Juan 3:16" autocomplete="off">
    </form>
    <div class="topnav">
        <?php if (!empty($versions)): ?>
        <a class="navlink" href="<?= e(url('buscar')) ?>" title="Buscar en la Biblia" aria-label="Buscar en la Biblia">⌕</a>
        <?php endif; ?>
        <button type="button" id="themeBtn" title="Modo oscuro" aria-label="Modo oscuro">☾</button>
        <button type="button" id="prefBtn" title="Apariencia" aria-label="Apariencia">⚙</button>
    </div>
</header>

<main class="page" id="main-content">
<?php if (!empty($meta['crumbs'])): ?>
<nav class="crumbs" aria-label="Breadcrumb"><?php
    $last = count($meta['crumbs']) - 1;
    foreach ($meta['crumbs'] as $i => $c): ?>
        <?php if ($i): ?><span class="crumb-sep" aria-hidden="true">›</span><?php endif; ?>
        <?php if ($c['url'] && $i < $last): ?><a class="crumb" href="<?= e($c['url']) ?>"><?= e($c['label']) ?></a>
        <?php else: ?><span class="crumb<?= $i === $last ? ' cur' : '' ?>" aria-current="<?= $i === $last ? 'page' : 'false' ?>"><?= e($c['label']) ?></span><?php endif; ?>
    <?php endforeach; ?>
</nav>
<?php endif; ?>
<?= $content ?>
</main>

<footer class="footer">
    <?php if (!empty($version)): ?>
    <p><?= e($version['name']) ?><?= !empty($version['copyright']) ? ' · ' . e($version['copyright']) : '' ?></p>
    <?php endif; ?>
    <p>Biblia Fácil — lee la Biblia, fácil. ·
        <a href="<?= e(url('temas')) ?>">Temas</a> ·
        <a href="<?= e(url('planes')) ?>">Planes de lectura</a> ·
        <a href="<?= e(url('versiculo-del-dia')) ?>">Versículo del día</a> ·
        <a href="<?= e(url('guias')) ?>">Guías</a> ·
        <a href="<?= e(url('juegos')) ?>">Juegos</a></p>
    <?php if (!empty($visits) && env('FF_COUNTER', '1') === '1'): ?>
    <p class="visits"><span class="visits-ico" aria-hidden="true">✝</span><strong><?= number_format($visits[1]) ?></strong> visitas</p>
    <?php endif; ?>
</footer>

<script src="<?= e(asset('app.js')) ?>"></script>
<?php foreach (($extraJs ?? []) as $j): ?>
<script src="<?= e(asset($j)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
