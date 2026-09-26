<div class="page-hero">
    <h1>🌅 Versículo del día</h1>
    <p class="ver-note"><?= e($version['name']) ?> · <?= e($fechaTxt) ?></p>
</div>

<?php if ($votd): ?>
<article class="tema-verse card votd-big">
    <blockquote><?= \Biblia\Bible\VerseText::render($votd['text'], $votd['wj'] ?? null) ?></blockquote>
    <a class="tema-ref" href="<?= e(url("{$version['code']}/{$votd['book_slug']}/{$votd['chapter']}#v{$votd['verse']}")) ?>">
        <?= e($votd['book_name'] . ' ' . $votd['chapter'] . ':' . $votd['verse']) ?> → leer el capítulo
    </a>
</article>
<?php endif; ?>

<nav class="votd-nav">
    <?php if ($prev): ?><a class="nav-btn" href="<?= e(url('versiculo-del-dia?d=' . $prev)) ?>">← Ayer</a><?php endif; ?>
    <?php if ($next): ?><a class="nav-btn" href="<?= e(url('versiculo-del-dia?d=' . $next)) ?>">Mañana →</a><?php endif; ?>
</nav>

<p class="ver-note" style="margin-top:1.2rem">Últimos días:
    <?php foreach ($archive as $d): ?>
        <a class="chip" href="<?= e(url('versiculo-del-dia?d=' . $d)) ?>"><?= e(substr($d, 5)) ?></a>
    <?php endforeach; ?>
</p>
<p class="ver-note"><a href="<?= e(url('versiculo-del-dia/rss')) ?>">RSS 📡</a> — suscríbete para recibirlo cada día</p>

<?php if ($votd): ?>
<p class="share-label">Comparte el versículo del día:</p>
<?= sharebar(\Biblia\Core\Seo::abs('versiculo-del-dia' . ($d !== date('Y-m-d') ? '?d=' . $d : '')), 'Versículo del día: ' . $votd['book_name'] . ' ' . $votd['chapter'] . ':' . $votd['verse'] . ' — Biblia Fácil') ?>
<?php endif; ?>
