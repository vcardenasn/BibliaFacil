<div class="page-hero">
    <h1>📚 Guías</h1>
    <p>Aprende a usar y disfrutar la Biblia.</p>
</div>

<div class="temas-grid">
    <?php foreach ($guias as $slug => $g): ?>
    <a class="tema-card" href="<?= e(url('guias/' . $slug)) ?>">
        <span class="tema-emoji" aria-hidden="true"><?= e($g['emoji']) ?></span>
        <strong><?= e($g['title']) ?></strong>
        <small><?= e($g['desc']) ?></small>
    </a>
    <?php endforeach; ?>
</div>
