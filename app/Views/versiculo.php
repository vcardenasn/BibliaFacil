<div class="page-hero">
    <h1>✝️ <?= e($entry['title']) ?></h1>
    <p><?= e($entry['context']) ?></p>
</div>

<div class="verses tema-verses">
    <?php foreach ($texts as $t): ?>
    <article class="tema-verse card">
        <span class="mi-ver"><?= e(strtoupper($t['code'])) ?></span>
        <blockquote><?= \Biblia\Bible\VerseText::render($t['text'], $t['wj'] ?? null) ?></blockquote>
        <a class="tema-ref" href="<?= e(url("{$t['code']}/{$t['book_slug']}/{$t['chapter']}#v{$t['verse']}")) ?>">
            <?= e($t['book_name'] . ' ' . $t['chapter'] . ':' . $t['verse']) ?> · <?= e($t['name']) ?> →
        </a>
    </article>
    <?php endforeach; ?>
</div>

<p class="share-label">Comparte este versículo:</p>
<?= sharebar(\Biblia\Core\Seo::abs('versiculo/' . $entry['slug']), $entry['title'] . ' — Biblia Fácil') ?>
