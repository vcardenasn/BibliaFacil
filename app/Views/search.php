<h1>Buscar en la Biblia</h1>

<form class="search-form" method="get" action="<?= e(url('buscar')) ?>" role="search">
    <label class="search-label" for="sq">Palabra o frase</label>
    <input id="sq" type="search" name="q" value="<?= e($q ?? '') ?>"
        placeholder="Ej.: amor, paz, fe" autofocus
        aria-describedby="sq-help">
    <label class="sr-only" for="sv">Versión</label>
    <select id="sv" name="v">
        <?php foreach ($versions as $v): ?>
        <option value="<?= e($v['code']) ?>"<?= $version && $v['id'] === $version['id'] ? ' selected' : '' ?>>
            <?= e($v['name']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Buscar</button>
</form>
<p class="search-hint" id="sq-help">
    Busca <strong>palabras del texto</strong> (amor, paz, fe…).
    Para ir a una cita exacta usa <em>“Ir a”</em> en la barra superior — por ejemplo <strong>Juan 3:16</strong>.
</p>

<?php if (($q ?? '') !== ''): ?>
<p class="muted" role="status"><?= count($results) ?> resultado(s) para “<?= e($q) ?>” en <?= e($version['name'] ?? '') ?>.</p>
<ul class="results">
    <?php foreach ($results as $r): ?>
    <li class="card result">
        <a href="<?= e(url("{$version['code']}/{$r['book_slug']}/{$r['chapter']}#v{$r['verse']}")) ?>">
            <?= e("{$r['book_name']} {$r['chapter']}:{$r['verse']}") ?>
        </a>
        <p><?= preg_replace('/(' . preg_quote(e($q), '/') . ')/iu', '<mark>$1</mark>', e($r['text'])) ?></p>
        <button type="button" class="ctx-btn" aria-expanded="false"
            data-v="<?= e($version['code']) ?>" data-b="<?= e($r['book_slug']) ?>"
            data-c="<?= (int) $r['chapter'] ?>" data-n="<?= (int) $r['verse'] ?>">⌄ Contexto ±3</button>
    </li>
    <?php endforeach; ?>
    <?php if (!$results): ?>
    <li class="card search-empty">
        <p><strong>Sin resultados para “<?= e($q) ?>”.</strong></p>
        <p>Prueba una palabra más corta, otra grafía, o busca en otra versión desde el selector de arriba.</p>
        <p class="search-empty-links">
            <a href="<?= e(url('temas')) ?>">Explorar por tema</a> ·
            <a href="<?= e(url($version['code'] ?? '')) ?>">Ver los libros</a>
        </p>
    </li>
    <?php endif; ?>
</ul>
<?php endif; ?>
