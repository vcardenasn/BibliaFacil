<h1><?= e(t('Buscar en la Biblia')) ?></h1>

<form class="search-form" method="get" action="<?= e(url('buscar')) ?>" role="search">
    <label class="search-label" for="sq"><?= e(t('Palabra o frase')) ?></label>
    <input id="sq" type="search" name="q" value="<?= e($q ?? '') ?>"
        placeholder="<?= e(t('Ej.: amor, paz, fe')) ?>" autofocus
        aria-describedby="sq-help">
    <label class="sr-only" for="sv"><?= e(t('Versión')) ?></label>
    <select id="sv" name="v">
        <?php foreach ($versions as $v): ?>
        <option value="<?= e($v['code']) ?>"<?= $version && $v['id'] === $version['id'] ? ' selected' : '' ?>>
            <?= e($v['name']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <button type="submit"><?= e(t('Buscar')) ?></button>
</form>
<p class="search-hint" id="sq-help">
    <?= e(t('Busca')) ?> <strong><?= e(t('palabras del texto')) ?></strong> (<?= e(t('amor, paz, fe')) ?>…).
    <?= e(t('Para ir a una cita exacta usa')) ?> <em>“<?= e(t('Ir a')) ?>”</em> <?= e(t('en la barra superior')) ?> — <?= e(t('por ejemplo')) ?> <strong>Juan 3:16</strong>.
</p>

<?php if (($q ?? '') !== ''): ?>
<p class="muted" role="status"><?= count($results) ?> <?= e(t('resultado(s) para')) ?> “<?= e($q) ?>” <?= e(t('en')) ?> <?= e($version['name'] ?? '') ?>.</p>
<ul class="results">
    <?php foreach ($results as $r): ?>
    <li class="card result">
        <a href="<?= e(url("{$version['code']}/{$r['book_slug']}/{$r['chapter']}#v{$r['verse']}")) ?>">
            <?= e(t($r['book_name']) . " {$r['chapter']}:{$r['verse']}") ?>
        </a>
        <p><?= preg_replace('/(' . preg_quote(e($q), '/') . ')/iu', '<mark>$1</mark>', e($r['text'])) ?></p>
        <button type="button" class="ctx-btn" aria-expanded="false"
            data-v="<?= e($version['code']) ?>" data-b="<?= e($r['book_slug']) ?>"
            data-c="<?= (int) $r['chapter'] ?>" data-n="<?= (int) $r['verse'] ?>">⌄ <?= e(t('Contexto ±3')) ?></button>
    </li>
    <?php endforeach; ?>
    <?php if (!$results): ?>
    <li class="card search-empty">
        <p><strong><?= e(t('Sin resultados para')) ?> “<?= e($q) ?>”.</strong></p>
        <p><?= e(t('Prueba una palabra más corta, otra grafía, o busca en otra versión desde el selector de arriba.')) ?></p>
        <p class="search-empty-links">
            <a href="<?= e(url('temas')) ?>"><?= e(t('Explorar por tema')) ?></a> ·
            <a href="<?= e(url($version['code'] ?? '')) ?>"><?= e(t('Ver los libros')) ?></a>
        </p>
    </li>
    <?php endif; ?>
</ul>
<?php endif; ?>
