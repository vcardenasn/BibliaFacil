<h1>Buscar en la Biblia</h1>

<form class="search-form" method="get" action="<?= e(url('buscar')) ?>">
    <input type="search" name="q" value="<?= e($q ?? '') ?>" placeholder="Palabra o frase…" autofocus>
    <select name="v" aria-label="Versión">
        <?php foreach ($versions as $v): ?>
        <option value="<?= e($v['code']) ?>"<?= $version && $v['id'] === $version['id'] ? ' selected' : '' ?>>
            <?= e($v['name']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Buscar</button>
</form>

<?php if (($q ?? '') !== ''): ?>
<p class="muted"><?= count($results) ?> resultado(s) para “<?= e($q) ?>” en <?= e($version['name'] ?? '') ?>.</p>
<ul class="results">
    <?php foreach ($results as $r): ?>
    <li class="card result">
        <a href="<?= e(url("{$version['code']}/{$r['book_slug']}/{$r['chapter']}#v{$r['verse']}")) ?>">
            <?= e("{$r['book_name']} {$r['chapter']}:{$r['verse']}") ?>
        </a>
        <p><?= preg_replace('/(' . preg_quote(e($q), '/') . ')/iu', '<mark>$1</mark>', e($r['text'])) ?></p>
    </li>
    <?php endforeach; ?>
    <?php if (!$results): ?>
    <li class="muted">Sin resultados — prueba otra palabra o versión.</li>
    <?php endif; ?>
</ul>
<?php endif; ?>
