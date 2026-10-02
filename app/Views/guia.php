<div class="page-hero">
    <h1><?= e($guia['emoji']) ?> <?= e(t($guia['title'])) ?></h1>
    <p class="ver-note"><a href="<?= e(url('guias')) ?>">← <?= e(t('todas las guías')) ?></a></p>
</div>

<div class="card guia-body" lang="<?= e(lang()) ?>">
    <?= $guia['html'] /* contenido editorial propio, ya sanitizado */ ?>
</div>
