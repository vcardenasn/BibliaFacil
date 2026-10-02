<div class="page-hero">
    <h1>📚 <?= e(t('Guías')) ?></h1>
    <p><?= e(t('Aprende a usar y disfrutar la Biblia.')) ?></p>
</div>

<div class="temas-grid">
    <?php foreach ($guias as $slug => $g): ?>
    <a class="tema-card" href="<?= e(url('guias/' . $slug)) ?>">
        <span class="tema-emoji" aria-hidden="true"><?= e($g['emoji']) ?></span>
        <strong><?= e(t($g['title'])) ?></strong>
        <small><?= e(t($g['desc'])) ?></small>
    </a>
    <?php endforeach; ?>
</div>
