<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Biblia Fácil') ?> · Biblia Fácil</title>
<meta name="description" content="Lee la Biblia en múltiples versiones, fácil y rápido.">
<link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📖</text></svg>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= e(url('/')) ?>">📖 Biblia Fácil</a>
    <form class="goto" method="get" action="<?= e(url('ir')) ?>" role="search">
        <?php if (!empty($version)): ?>
        <input type="hidden" name="v" value="<?= e($version['code']) ?>">
        <?php endif; ?>
        <input type="text" name="q" placeholder="Ir a: Juan 3:16" aria-label="Ir a referencia" autocomplete="off">
    </form>
    <nav class="topnav">
        <?php if (!empty($versions)): ?>
        <form method="get" action="<?= e(url('buscar')) ?>" class="searchlink">
            <button type="submit" title="Buscar">🔍</button>
        </form>
        <?php endif; ?>
        <button type="button" id="fontBtn" title="Tamaño de letra">A±</button>
        <button type="button" id="themeBtn" title="Modo oscuro">◐</button>
    </nav>
</header>

<main class="page">
<?= $content ?>
</main>

<footer class="footer">
    <?php if (!empty($version)): ?>
    <p><?= e($version['copyright'] ?? '') ?><?= !empty($version['copyright']) ? ' · ' : '' ?><?= e($version['name']) ?></p>
    <?php endif; ?>
    <p>Biblia Fácil — lee la Biblia, fácil.</p>
</footer>

<script src="<?= e(asset('app.js')) ?>"></script>
</body>
</html>
