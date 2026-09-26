<div class="page-hero">
    <h1>📖 Versículos por tema</h1>
    <p>Colecciones de versículos para cada momento — amor, fe, ánimo, familia y más.</p>
</div>

<div class="temas-grid">
    <?php foreach ($temas as $slug => $t): ?>
    <a class="tema-card" href="<?= e(url('temas/' . $slug)) ?>">
        <span class="tema-emoji" aria-hidden="true"><?= e($t['emoji']) ?></span>
        <strong><?= e($t['name']) ?></strong>
        <small><?= count($t['refs']) ?> versículos</small>
    </a>
    <?php endforeach; ?>
</div>
