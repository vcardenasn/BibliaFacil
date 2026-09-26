<div class="page-hero">
    <h1><?= e($guia['emoji']) ?> <?= e($guia['title']) ?></h1>
    <p class="ver-note"><a href="<?= e(url('guias')) ?>">← todas las guías</a></p>
</div>

<div class="card guia-body">
    <?= $guia['html'] /* contenido editorial propio, ya sanitizado */ ?>
</div>
