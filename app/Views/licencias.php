<div class="page-hero">
    <h1><span aria-hidden="true">⚖️</span> Licencias y copyright</h1>
    <p>Atribución de las versiones bíblicas y recursos usados en Biblia Fácil.</p>
</div>

<section class="card">
    <h2>Versiones de la Biblia</h2>
    <p class="muted">Biblia Fácil es un proyecto comunitario sin fines de lucro: sin anuncios, sin suscripciones y sin compras.</p>
    <ul class="license-list">
    <?php
    $apiSourced = array_filter($versions ?? [], fn ($v) => !empty($v['api_bible_id']));
    foreach ($versions ?? [] as $v):
        $abbr = strtoupper((string) $v['code']);
    ?>
    <li class="license-item" id="<?= e($v['code']) ?>">
        <h3><?= e($v['name']) ?> (<?= e($abbr) ?>)</h3>
        <?php if (!empty($v['api_bible_id'])): ?>
        <p>Los textos de las Escrituras marcados <?= e($abbr) ?> © son tomados de
            <?= e($v['name']) ?>. <?= e((string) ($v['copyright'] ?? '')) ?>
            Usada con permiso. Todos los derechos reservados. El texto <?= e($abbr) ?>
            no puede ser citado en ninguna publicación disponible al público bajo
            licencia Creative Commons, ni traducido a otro idioma.
            <?php if (!empty($v['source_url'])): ?>
            <a href="<?= e($v['source_url']) ?>" target="_blank" rel="noopener">Sitio web oficial<span class="sr-only"> (abre en otra pestaña)</span></a>
            <?php endif; ?>
        </p>
        <?php else: ?>
        <p>Los textos de las Escrituras marcados <?= e($abbr) ?> son tomados de
            <?= e($v['name']) ?>. <?= e((string) ($v['copyright'] ?? '')) ?>
            <?php if (!empty($v['source_url'])): ?>
            <a href="<?= e($v['source_url']) ?>" target="_blank" rel="noopener">Fuente<span class="sr-only"> (abre en otra pestaña)</span></a>
            <?php endif; ?>
        </p>
        <?php endif; ?>
    </li>
    <?php endforeach; ?>
    <?php if ($apiSourced): ?>
    <li class="license-item" id="apibible">
        <h3>API.Bible</h3>
        <p>El contenido de las versiones marcadas se provee vía
            <a href="https://api.bible" target="_blank" rel="noopener">API.Bible<span class="sr-only"> (abre en otra pestaña)</span></a>
            (American Bible Society) conforme a sus
            <a href="https://api.bible/terms-and-conditions" target="_blank" rel="noopener">Términos de Servicio<span class="sr-only"> (abre en otra pestaña)</span></a>,
            el Acuerdo de Uso Aceptable y el Acuerdo de Uso Estrictamente No Comercial.
            El texto no se almacena permanentemente en este sitio: se sirve desde la
            API con caché temporal.</p>
    </li>
    <?php endif; ?>
    </ul>
</section>

<section class="card">
    <h2>Ilustraciones e íconos</h2>
    <ul class="license-list">
    <li class="license-item">Íconos de juegos: <a href="https://openmoji.org" target="_blank" rel="noopener">OpenMoji<span class="sr-only"> (abre en otra pestaña)</span></a> — CC BY-SA 4.0.</li>
    <li class="license-item">Ilustraciones de personajes: sets adquiridos vía Freepik por el autor del sitio.</li>
    </ul>
</section>
