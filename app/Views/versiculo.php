<div class="page-hero">
    <h1>✝️ <?= e(t($entry['title'])) ?></h1>
    <p><?= e(t($entry['context'])) ?></p>
</div>

<div class="verses tema-verses">
    <?php foreach ($texts as $tx): ?>
    <article class="tema-verse card">
        <span class="mi-ver"><?= e(strtoupper($tx['code'])) ?></span>
        <blockquote><?= \Biblia\Bible\VerseText::render($tx['text'], $tx['wj'] ?? null) ?></blockquote>
        <a class="tema-ref" href="<?= e(url("{$tx['code']}/{$tx['book_slug']}/{$tx['chapter']}#v{$tx['verse']}")) ?>">
            <?= e(t($tx['book_name']) . ' ' . $tx['chapter'] . ':' . $tx['verse']) ?> · <?= e($tx['name']) ?> →
        </a>
    </article>
    <?php endforeach; ?>
</div>

<p class="share-label"><?= e(t('Comparte este versículo:')) ?></p>
<?= sharebar(\Biblia\Core\Seo::abs('versiculo/' . $slug), t($entry['title']) . ' — Palabra Fiel') ?>
