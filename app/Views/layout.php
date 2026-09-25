<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Biblia Fácil') ?> · Biblia Fácil</title>
<meta name="description" content="Lee la Biblia en múltiples versiones, fácil y rápido.">
<meta name="theme-color" content="#2e4a8a">
<link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📖</text></svg>">
</head>
<body>
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
        <a class="navlink" href="<?= e(url('mias')) ?>" title="Mis anotaciones" aria-label="Mis anotaciones">✎</a>
        <button type="button" id="themeBtn" title="Modo oscuro" aria-label="Modo oscuro">☾</button>
        <button type="button" id="prefBtn" title="Apariencia" aria-label="Apariencia">⚙</button>
    </nav>
</header>

<main class="page">
<?= $content ?>
</main>

<footer class="footer">
    <?php if (!empty($version)): ?>
    <p><?= e($version['name']) ?><?= !empty($version['copyright']) ? ' · ' . e($version['copyright']) : '' ?></p>
    <?php endif; ?>
    <p>Biblia Fácil — lee la Biblia, fácil.</p>
    <?php if (!empty($visits) && env('FF_COUNTER', '1') === '1'): ?>
    <p class="visits">Hoy: <?= number_format($visits[0]) ?> · Visitas: <?= number_format($visits[1]) ?></p>
    <?php endif; ?>
</footer>

<script src="<?= e(asset('app.js')) ?>"></script>
</body>
</html>
