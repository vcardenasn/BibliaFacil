<div class="page-hero">
    <h1><span aria-hidden="true">⚖️</span> <?= e(t('Licencias y copyright')) ?></h1>
    <p><?= e(t('Atribución de las versiones bíblicas y recursos usados en Palabra Fiel.')) ?></p>
</div>

<section class="card">
    <h2><?= e(t('Versiones de la Biblia')) ?></h2>
    <p class="muted"><?= e(t('Palabra Fiel es un proyecto comunitario sin fines de lucro: sin anuncios, sin suscripciones y sin compras.')) ?></p>
    <ul class="license-list">
    <?php
    $apiSourced = array_filter($versions ?? [], fn ($v) => !empty($v['api_bible_id']));
    foreach ($versions ?? [] as $v):
        $abbr = strtoupper((string) $v['code']);
    ?>
    <li class="license-item" id="<?= e($v['code']) ?>">
        <h3><?= e($v['name']) ?> (<?= e($abbr) ?>)</h3>
        <?php if (!empty($v['api_bible_id'])): ?>
        <p><?= e(t('Los textos de las Escrituras marcados')) ?> <?= e($abbr) ?> © <?= e(t('son tomados de')) ?>
            <?= e($v['name']) ?>. <?= e((string) ($v['copyright'] ?? '')) ?>
            <?= e(t('Usada con permiso. Todos los derechos reservados. El texto')) ?> <?= e($abbr) ?>
            <?= e(t('no puede ser citado en ninguna publicación disponible al público bajo licencia Creative Commons, ni traducido a otro idioma.')) ?>
            <?php if (!empty($v['source_url'])): ?>
            <a href="<?= e($v['source_url']) ?>" target="_blank" rel="noopener"><?= e(t('Sitio web oficial')) ?><span class="sr-only"> (<?= e(t('abre en otra pestaña')) ?>)</span></a>
            <?php endif; ?>
        </p>
        <?php else: ?>
        <p><?= e(t('Los textos de las Escrituras marcados')) ?> <?= e($abbr) ?> <?= e(t('son tomados de')) ?>
            <?= e($v['name']) ?>. <?= e((string) ($v['copyright'] ?? '')) ?>
            <?php if (!empty($v['source_url'])): ?>
            <a href="<?= e($v['source_url']) ?>" target="_blank" rel="noopener"><?= e(t('Fuente')) ?><span class="sr-only"> (<?= e(t('abre en otra pestaña')) ?>)</span></a>
            <?php endif; ?>
        </p>
        <?php endif; ?>
    </li>
    <?php endforeach; ?>
    <?php if ($apiSourced): ?>
    <li class="license-item" id="apibible">
        <h3>API.Bible</h3>
        <p><?= e(t('El contenido de las versiones marcadas se provee vía')) ?>
            <a href="https://api.bible" target="_blank" rel="noopener">API.Bible<span class="sr-only"> (<?= e(t('abre en otra pestaña')) ?>)</span></a>
            <?= e(t('(American Bible Society) conforme a sus')) ?>
            <a href="https://api.bible/terms-and-conditions" target="_blank" rel="noopener"><?= e(t('Términos de Servicio')) ?><span class="sr-only"> (<?= e(t('abre en otra pestaña')) ?>)</span></a>,
            <?= e(t('el Acuerdo de Uso Aceptable y el Acuerdo de Uso Estrictamente No Comercial. El texto no se almacena permanentemente en este sitio: se sirve desde la API con caché temporal.')) ?></p>
    </li>
    <?php endif; ?>
    </ul>
</section>

<section class="card">
    <h2><?= e(t('Ilustraciones e íconos')) ?></h2>
    <ul class="license-list">
    <li class="license-item"><?= e(t('Íconos de juegos:')) ?> <a href="https://openmoji.org" target="_blank" rel="noopener">OpenMoji<span class="sr-only"> (<?= e(t('abre en otra pestaña')) ?>)</span></a> — CC BY-SA 4.0.</li>
    <li class="license-item"><?= e(t('Ilustraciones de personajes: sets adquiridos vía Freepik por el autor del sitio.')) ?></li>
    </ul>
</section>
