<p class="crumbs">
    <a href="<?= e(url($version['code'])) ?>"><?= e($version['name']) ?></a> ›
    <?= e($book['name']) ?>
</p>

<h1><?= e($book['name']) ?></h1>
<p class="muted">Elige un capítulo.</p>

<ul class="chapter-grid">
    <?php for ($c = 1; $c <= (int) $book['chapters']; $c++): ?>
    <li><a href="<?= e(url("{$version['code']}/{$book['slug']}/{$c}")) ?>"><?= $c ?></a></li>
    <?php endfor; ?>
</ul>
