<?php if ($votd): ?>
<section class="votd card">
    <span class="votd-label"><?= e(t('Versículo del día')) ?></span>
    <blockquote><?= e($votd['text']) ?></blockquote>
    <a class="votd-ref" href="<?= e(url("{$version['code']}/{$votd['book_slug']}/{$votd['chapter']}#v{$votd['verse']}")) ?>">
        <?= e(t($votd['book_name']) . ' ' . $votd['chapter'] . ':' . $votd['verse']) ?> →
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
<p class="muted"><?= e(t('Elige un libro para empezar a leer.')) ?></p>
<?php if (!empty($version['copyright'])): ?>
<p class="muted copyright"><?= e($version['copyright']) ?></p>
<?php endif; ?>

<div class="book-starter card">
    <div><strong><?= e(t('¿No sabes por dónde empezar?')) ?></strong><p><?= e(t('Juan cuenta la vida y las enseñanzas de Jesús en 21 capítulos.')) ?></p></div>
    <a href="<?= e(url("{$version['code']}/juan/1")) ?>"><?= e(t('Empieza por Juan 1')) ?> →</a>
</div>
<div class="book-tools">
    <label for="book-filter"><?= e(t('Buscar un libro')) ?></label>
    <input id="book-filter" type="search" placeholder="<?= e(t('Por ejemplo: Salmos o Juan')) ?>" autocomplete="off" aria-controls="books-at books-nt">
    <p id="book-filter-status" role="status" aria-live="polite" hidden></p>
    <nav aria-label="<?= e(t('Ir al testamento')) ?>"><a href="#books-at"><?= e(t('Antiguo Testamento')) ?></a><a href="#books-nt"><?= e(t('Nuevo Testamento')) ?></a></nav>
</div>

<?php foreach (['AT' => t('Antiguo Testamento'), 'NT' => t('Nuevo Testamento')] as $t => $label): ?>
<section class="book-section" id="books-<?= strtolower($t) ?>" aria-label="<?= e($label) ?>">
    <h2><?= e($label) ?></h2>
    <ul class="book-grid">
        <?php foreach ($books as $b): ?>
            <?php if ($b['testament'] !== $t) continue; ?>
        <li><a href="<?= e(url("{$version['code']}/{$b['slug']}")) ?>"><?= e(t($b['name'])) ?></a></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endforeach; ?>
