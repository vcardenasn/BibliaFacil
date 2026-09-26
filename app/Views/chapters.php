<h1><?= e($book['name']) ?></h1>
<?php if (!empty($intro)): ?>
<p class="book-intro"><?= e($intro) ?></p>
<?php endif; ?>
<p class="muted">Elige un capítulo — <?= (int) $book['chapters'] ?> en total.</p>

<ul class="chapter-grid">
    <?php for ($c = 1; $c <= (int) $book['chapters']; $c++): ?>
    <li><a href="<?= e(url("{$version['code']}/{$book['slug']}/{$c}")) ?>"><?= $c ?></a></li>
    <?php endfor; ?>
</ul>
