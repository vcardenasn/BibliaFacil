<?php if ($votd): ?>
<section class="votd card">
    <span class="votd-label">Versículo del día</span>
    <blockquote><?= e($votd['text']) ?></blockquote>
    <a class="votd-ref" href="<?= e(url("{$version['code']}/{$votd['book_slug']}/{$votd['chapter']}#v{$votd['verse']}")) ?>">
        <?= e($votd['book_name'] . ' ' . $votd['chapter'] . ':' . $votd['verse']) ?> →
    </a>
</section>
<?php endif; ?>

<nav class="version-pills">
    <?php foreach ($versions as $v): ?>
    <a class="pill<?= $v['id'] === $version['id'] ? ' active' : '' ?>" href="<?= e(url($v['code'])) ?>">
        <?= e($v['code']) ?>
    </a>
    <?php endforeach; ?>
</nav>

<h1><?= e($version['name']) ?></h1>
<p class="muted">Elige un libro para empezar a leer.</p>

<?php foreach (['AT' => 'Antiguo Testamento', 'NT' => 'Nuevo Testamento'] as $t => $label): ?>
<h2><?= e($label) ?></h2>
<ul class="book-grid">
    <?php foreach ($books as $b): ?>
        <?php if ($b['testament'] !== $t) continue; ?>
    <li><a href="<?= e(url("{$version['code']}/{$b['slug']}")) ?>"><?= e($b['name']) ?></a></li>
    <?php endforeach; ?>
</ul>
<?php endforeach; ?>
