<div class="page-hero">
    <h1><?= e($tema['emoji']) ?> <?= e(t($tema['name'])) ?></h1>
    <p><?= e(t($tema['intro'])) ?></p>
    <p class="ver-note"><?= e($version['name']) ?> — <a href="<?= e(url('temas')) ?>">← <?= e(t('todos los temas')) ?></a></p>
</div>

<div class="verses tema-verses">
    <?php foreach ($verses as $v): ?>
    <article class="tema-verse card">
        <blockquote><?= \Biblia\Bible\VerseText::render($v['text'], $v['wj'] ?? null) ?></blockquote>
        <a class="tema-ref" href="<?= e(url("{$version['code']}/{$v['book_slug']}/{$v['chapter']}#v{$v['verse']}")) ?>">
            <?= e(t($v['book_name']) . ' ' . $v['chapter'] . ':' . $v['verse']) ?> →
        </a>
    </article>
    <?php endforeach; ?>
</div>

<p class="share-label"><?= e(t('Comparte este tema:')) ?></p>
<?= sharebar(\Biblia\Core\Seo::abs('temas/' . $slug), t('Versículos sobre') . ' ' . t($tema['name']) . ' — Palabra Fiel') ?>
