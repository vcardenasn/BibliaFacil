<p class="crumbs">
    <a href="<?= e(url($version['code'])) ?>"><?= e($version['name']) ?></a> ›
    <a href="<?= e(url("{$version['code']}/{$book['slug']}")) ?>"><?= e($book['name']) ?></a> ›
    <?= (int) $chapter ?>
</p>

<div class="reader-head">
    <?php if ($nav['prev']): ?>
    <a class="nav-btn" href="<?= e(url(ltrim($nav['prev'], '/'))) ?>" rel="prev">← Anterior</a>
    <?php else: ?><span class="nav-btn disabled"></span><?php endif; ?>

    <h1><?= e($book['name']) ?> <?= (int) $chapter ?></h1>

    <?php if ($nav['next']): ?>
    <a class="nav-btn" href="<?= e(url(ltrim($nav['next'], '/'))) ?>" rel="next">Siguiente →</a>
    <?php else: ?><span class="nav-btn disabled"></span><?php endif; ?>
</div>

<?php if (!$verses): ?>
<section class="card notice">
    <p>Esta versión aún no tiene contenido cargado.</p>
    <p class="muted">Si eres el administrador: <code>php scripts/import_bible.php --file=... --code=<?= e($version['code']) ?></code></p>
</section>
<?php else: ?>
<article class="chapter" data-pos="<?= e("{$version['code']}/{$book['slug']}/{$chapter}") ?>">
    <?php foreach ($verses as $v): ?>
    <p class="verse" id="v<?= (int) $v['verse'] ?>" data-ref="<?= e("{$book['name']} {$chapter}:{$v['verse']}") ?>" data-text="<?= e($v['text']) ?>">
        <sup><?= (int) $v['verse'] ?></sup><?= e($v['text']) ?>
        <span class="verse-actions">
            <button type="button" class="va" data-act="copy" title="Copiar">⧉</button>
            <button type="button" class="va" data-act="share" title="Compartir">↗</button>
        </span>
    </p>
    <?php endforeach; ?>
</article>
<?php endif; ?>

<div class="reader-foot">
    <?php if ($nav['prev']): ?>
    <a class="nav-btn" href="<?= e(url(ltrim($nav['prev'], '/'))) ?>" rel="prev">← <?= e('Anterior') ?></a>
    <?php else: ?><span></span><?php endif; ?>
    <a class="nav-btn up" href="<?= e(url("{$version['code']}/{$book['slug']}")) ?>">Capítulos</a>
    <?php if ($nav['next']): ?>
    <a class="nav-btn" href="<?= e(url(ltrim($nav['next'], '/'))) ?>" rel="next"><?= e('Siguiente') ?> →</a>
    <?php else: ?><span></span><?php endif; ?>
</div>
