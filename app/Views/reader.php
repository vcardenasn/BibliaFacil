<p class="crumbs">
    <a href="<?= e(url($version['code'])) ?>"><?= e(strtoupper($version['code'])) ?></a> ›
    <a href="<?= e(url("{$version['code']}/{$book['slug']}")) ?>"><?= e(t($book['name'])) ?></a> ›
    <?= (int) $chapter ?>
</p>

<div class="reader-head">
    <?php if ($nav['prev']): ?>
    <a class="nav-btn" href="<?= e(url(ltrim($nav['prev'], '/'))) ?>" rel="prev">← <?= e(t('Ant.')) ?></a>
    <?php else: ?><span class="nav-btn disabled"></span><?php endif; ?>

    <h1><?= e(t($book['name'])) ?><span class="chapnum"><?= e(t('Capítulo')) ?> <?= (int) $chapter ?></span></h1>

    <?php if ($nav['next']): ?>
    <a class="nav-btn" href="<?= e(url(ltrim($nav['next'], '/'))) ?>" rel="next"><?= e(t('Sig.')) ?> →</a>
    <?php else: ?><span class="nav-btn disabled"></span><?php endif; ?>
</div>

<?php if (!$verses): ?>
<section class="card notice">
    <p><?= e(t('Esta versión aún no tiene contenido cargado.')) ?></p>
    <?php if (!empty($version['api_bible_id'])): ?>
    <p class="muted"><?= e(t('Se sirve vía API.Bible — revisa <code>API_BIBLE_KEY</code> en .env y la conectividad.')) ?></p>
    <?php else: ?>
    <p class="muted"><?= e(t('Si eres el administrador:')) ?> <code>php scripts/import_bible.php --file=... --code=<?= e($version['code']) ?></code></p>
    <?php endif; ?>
</section>
<?php else: ?>
<article class="chapter" data-pos="<?= e("{$version['code']}/{$book['slug']}/{$chapter}") ?>" data-label="<?= e(t($book['name']) . " {$chapter}") ?>" data-tts="<?= \Biblia\Bible\VersionLicense::ttsOk($version) ? '1' : '0' ?>"<?= !empty($cmpUrl) ? ' data-cmp="' . e(url($cmpUrl)) . '"' : '' ?>>
    <?php foreach ($verses as $v): ?>
    <p class="verse<?= (int) $v['verse'] === 1 ? ' first-verse' : '' ?>" id="v<?= (int) $v['verse'] ?>" data-ref="<?= e(t($book['name']) . " {$chapter}:{$v['verse']}") ?>" data-text="<?= e($v['text']) ?>">
        <sup><?= (int) $v['verse'] ?></sup><?= verseHtml($v['text'], $v['wj'] ?? null, (int) $v['verse'] === 1) ?>
    </p>
    <?php endforeach; ?>
</article>
<?php endif; ?>

<?php if ($verses): ?>
<p class="verse-src muted"><?= e(strtoupper($version['code'])) ?> ·
    <a href="<?= e(url('licencias')) ?>#<?= e($version['code']) ?>"><?= e(t('Copyright y atribución')) ?></a>
    <?php if (!\Biblia\Bible\VersionLicense::ttsOk($version)): ?>· <?= e(t('Audio no disponible por licencia')) ?><?php endif; ?></p>
<?php endif; ?>

<?php if (!empty($cmpUrl)): ?>
<p class="reader-tools"><a href="<?= e(url($cmpUrl)) ?>">⇄ <?= e(t('Comparar con otra versión')) ?></a></p>
<?php endif; ?>

<div class="reader-foot">
    <?php if ($nav['prev']): ?>
    <a class="nav-btn" href="<?= e(url(ltrim($nav['prev'], '/'))) ?>" rel="prev">← <?= e(t('Anterior')) ?></a>
    <?php else: ?><span></span><?php endif; ?>
    <a class="nav-btn up" href="<?= e(url("{$version['code']}/{$book['slug']}")) ?>"><?= e(t('Capítulos')) ?></a>
    <?php if ($nav['next']): ?>
    <a class="nav-btn" href="<?= e(url(ltrim($nav['next'], '/'))) ?>" rel="next"><?= e(t('Siguiente')) ?> →</a>
    <?php else: ?><span></span><?php endif; ?>
</div>
