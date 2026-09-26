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
<header class="topbar">
    <a class="brand" href="<?= e(url('/')) ?>"><span class="cross">✝</span> Biblia Fácil</a>
    <?php if (!empty($versions) && !empty($version)): ?>
    <select class="vswitch" id="versionSwitch" data-version="<?= e($version['code']) ?>" aria-label="Cambiar versión" title="Cambiar versión">
        <?php foreach ($versions as $v): ?>
        <option value="<?= e($v['code']) ?>"<?= $v['id'] === $version['id'] ? ' selected' : '' ?>><?= e(strtoupper($v['code'])) ?></option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <form class="goto" method="get" action="<?= e(url('ir')) ?>" role="search">
        <?php if (!empty($version)): ?>
        <input type="hidden" name="v" value="<?= e($version['code']) ?>">
        <?php endif; ?>
        <input type="text" name="q" placeholder="Ir a: Juan 3:16" aria-label="Ir a referencia" autocomplete="off">
    </form>
    <nav class="topnav">
        <?php if (!empty($versions)): ?>
        <form method="get" action="<?= e(url('buscar')) ?>" class="searchlink">
            <button type="submit" title="Buscar" aria-label="Buscar">🔍</button>
        </form>
        <?php endif; ?>
        <a class="navlink" href="<?= e(url('juegos')) ?>" title="Juegos" aria-label="Juegos">🎮</a>
        <a class="navlink" href="<?= e(url('mias')) ?>" title="Mis anotaciones" aria-label="Mis anotaciones">✎</a>
        <button type="button" id="themeBtn" title="Modo oscuro" aria-label="Modo oscuro">☾</button>
        <button type="button" id="prefBtn" title="Apariencia" aria-label="Apariencia">⚙</button>
    </nav>
</header>

<main class="page">
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
