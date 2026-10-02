<div class="page-hero">
    <h1>📖 <?= e(t('Versículos por tema')) ?></h1>
    <p><?= e(t('Colecciones de versículos para cada momento — amor, fe, ánimo, familia y más.')) ?></p>
</div>

<div class="temas-grid">
    <?php foreach ($temas as $slug => $tm): ?>
    <a class="tema-card" href="<?= e(url('temas/' . $slug)) ?>">
        <span class="tema-emoji" aria-hidden="true"><?= e($tm['emoji']) ?></span>
        <strong><?= e(t($tm['name'])) ?></strong>
        <small><?= count($tm['refs']) ?> <?= e(t('versículos')) ?></small>
    </a>
    <?php endforeach; ?>
</div>
