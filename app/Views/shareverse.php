<div class="page-hero">
    <h1>✝️ <?= e($ref) ?></h1>
    <p class="ver-note"><?= e($version['name']) ?></p>
</div>

<article class="tema-verse card votd-big">
    <blockquote><?= \Biblia\Bible\VerseText::render($verse['text'], $verse['wj'] ?? null) ?></blockquote>
    <a class="tema-ref" href="<?= e($chapterUrl) ?>"><?= e($ref) ?> → leer el capítulo completo</a>
</article>

<p class="share-label">Comparte este versículo:</p>
<?= sharebar($shareUrl, '“' . mb_substr(strip_tags((string) $verse['text']), 0, 90) . '…” — ' . $ref) ?>

<p class="ver-note" style="margin-top:1.5rem"><img class="share-preview" src="<?= e($imgUrl) ?>" alt="Tarjeta de <?= e($ref) ?>" width="1200" height="630" loading="lazy"></p>
