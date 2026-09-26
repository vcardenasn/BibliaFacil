<?php
// EPIC 14 / US-140 — Comparador de dos versiones, lado a lado por versículo.
/** @var array|null $book @var array|null $va @var array|null $vb @var array $books @var array $versions @var array $versesA @var array $versesB @var array|null $nav */
?>
<?php if (!$book): ?>
<div class="page-hero">
    <h1>⇄ Comparar versiones</h1>
    <p>Elige un pasaje y dos versiones para leerlas lado a lado.</p>
</div>
<form class="cmp-pick card" method="get" action="<?= e(url('comparar')) ?>">
    <label for="cmp-book">Libro</label>
    <select id="cmp-book" name="book">
        <?php foreach ($books as $b): ?>
        <option value="<?= e($b['slug']) ?>"><?= e($b['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <label for="cmp-cap">Capítulo</label>
    <input id="cmp-cap" type="number" name="cap" value="1" min="1" max="150" inputmode="numeric">
    <label for="cmp-a">Versión A</label>
    <select id="cmp-a" name="a">
        <?php foreach ($versions as $v): ?>
        <option value="<?= e($v['code']) ?>"<?= $v['code'] === config('app.default_version', 'rvr1909') ? ' selected' : '' ?>><?= e($v['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <label for="cmp-b">Versión B</label>
    <select id="cmp-b" name="b">
        <?php foreach ($versions as $v): ?>
        <option value="<?= e($v['code']) ?>"<?= $v['code'] === 'onbv' ? ' selected' : '' ?>><?= e($v['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Comparar</button>
</form>
<?php else: ?>
<div class="reader-head">
    <?php if ($nav['prev']): ?>
    <a class="nav-btn" href="<?= e(url($nav['prev'])) ?>" rel="prev">← Ant.</a>
    <?php else: ?><span class="nav-btn disabled"></span><?php endif; ?>

    <h1><?= e($book['name']) ?><span class="chapnum">Capítulo <?= (int) $chapter ?> · comparación</span></h1>

    <?php if ($nav['next']): ?>
    <a class="nav-btn" href="<?= e(url($nav['next'])) ?>" rel="next">Sig. →</a>
    <?php else: ?><span class="nav-btn disabled"></span><?php endif; ?>
</div>

<form class="cmp-switch" method="get" action="<?= e(url('comparar')) ?>" aria-label="Cambiar versiones a comparar">
    <input type="hidden" name="book" value="<?= e($book['slug']) ?>">
    <input type="hidden" name="cap" value="<?= (int) $chapter ?>">
    <select name="a" aria-label="Versión A" onchange="this.form.submit()">
        <?php foreach ($versions as $v): ?>
        <option value="<?= e($v['code']) ?>"<?= $v['id'] === $va['id'] ? ' selected' : '' ?>><?= e($v['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <span aria-hidden="true">⇄</span>
    <select name="b" aria-label="Versión B" onchange="this.form.submit()">
        <?php foreach ($versions as $v): ?>
        <option value="<?= e($v['code']) ?>"<?= $v['id'] === $vb['id'] ? ' selected' : '' ?>><?= e($v['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <noscript><button type="submit">Comparar</button></noscript>
</form>

<div class="cmp-grid" role="table" aria-label="Comparación de <?= e($book['name']) ?> <?= (int) $chapter ?>">
    <div class="cmp-head" role="row">
        <span role="columnheader" aria-label="Versículo"></span>
        <div role="columnheader"><a href="<?= e(url("{$va['code']}/{$book['slug']}/{$chapter}")) ?>"><?= e(strtoupper($va['code'])) ?></a></div>
        <div role="columnheader"><a href="<?= e(url("{$vb['code']}/{$book['slug']}/{$chapter}")) ?>"><?= e(strtoupper($vb['code'])) ?></a></div>
    </div>
    <?php
    $nums = array_unique(array_merge(array_keys($versesA), array_keys($versesB)));
    sort($nums);
    foreach ($nums as $n):
        $rA = $versesA[$n] ?? null;
        $rB = $versesB[$n] ?? null;
    ?>
    <div class="cmp-row" id="v<?= (int) $n ?>" role="row">
        <span class="cmp-n" role="rowheader"><?= (int) $n ?></span>
        <div class="cmp-cell" role="cell" data-v="<?= e(strtoupper($va['code'])) ?>"><?= $rA ? verseHtml($rA['text'], $rA['wj'] ?? null) : '<span class="muted">—</span>' ?></div>
        <div class="cmp-cell" role="cell" data-v="<?= e(strtoupper($vb['code'])) ?>"><?= $rB ? verseHtml($rB['text'], $rB['wj'] ?? null) : '<span class="muted">—</span>' ?></div>
    </div>
    <?php endforeach; ?>
</div>

<p class="reader-tools">
    <a href="<?= e(url("{$va['code']}/{$book['slug']}/{$chapter}")) ?>">Leer en <?= e(strtoupper($va['code'])) ?></a> ·
    <a href="<?= e(url("{$vb['code']}/{$book['slug']}/{$chapter}")) ?>">Leer en <?= e(strtoupper($vb['code'])) ?></a>
</p>
<p class="muted cmp-copyright"><?= e($va['name']) ?><?= !empty($va['copyright']) ? ' · ' . e($va['copyright']) : '' ?><br><?= e($vb['name']) ?><?= !empty($vb['copyright']) ? ' · ' . e($vb['copyright']) : '' ?></p>
<?php endif; ?>
