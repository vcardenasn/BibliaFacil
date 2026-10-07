<h1><?= e(t($book['name'])) ?></h1>
<?php if (!empty($intro)): ?>
<p class="book-intro"><?= e(t($intro)) ?></p>
<?php endif; ?>
<p class="muted"><?= e(t('Elige un capítulo')) ?> — <?= (int) $book['chapters'] ?> <?= e(t('en total')) ?>.</p>
<?php if (empty($version['api_bible_id'])): ?>
<p><button type="button" id="dlOffline" class="dl-off" data-v="<?= e($version['code']) ?>" data-b="<?= e($book['slug']) ?>" data-n="<?= (int) $book['chapters'] ?>"><?= e(t('⬇ Descargar para leer sin conexión')) ?></button></p>
<?php endif; ?>

<ul class="chapter-grid">
    <?php for ($c = 1; $c <= (int) $book['chapters']; $c++): ?>
    <li><a href="<?= e(url("{$version['code']}/{$book['slug']}/{$c}")) ?>"><?= $c ?></a></li>
    <?php endfor; ?>
</ul>
