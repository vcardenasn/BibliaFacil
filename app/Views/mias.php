<h1><?= e(t('Mis anotaciones')) ?></h1>
<p class="muted"><?= e(t('Resaltados, notas, devocionales y favoritos — se guardan en este dispositivo.')) ?></p>

<div class="streak" id="streakBox" hidden></div>

<section class="hist card" id="histBox" hidden>
    <h2><?= e(t('Lectura reciente')) ?></h2>
    <ul class="hist-list" id="histList"></ul>
</section>

<div id="miasApp">
    <div class="mias-chips" role="radiogroup" aria-label="<?= e(t('Filtrar por tipo de anotación')) ?>">
        <button type="button" data-f="all" class="on" role="radio" aria-checked="true" tabindex="0"><?= e(t('Todas')) ?></button>
        <button type="button" data-f="hl" role="radio" aria-checked="false" tabindex="-1"><?= e(t('Resaltadas')) ?></button>
        <button type="button" data-f="note" role="radio" aria-checked="false" tabindex="-1"><?= e(t('Con nota')) ?></button>
        <button type="button" data-f="devotional" role="radio" aria-checked="false" tabindex="-1"><?= e(t('Devocionales')) ?></button>
        <button type="button" data-f="fav" role="radio" aria-checked="false" tabindex="-1"><?= e(t('Favoritas')) ?></button>
    </div>

    <div class="mias-tools">
        <input type="search" id="miasQ" placeholder="<?= e(t('Buscar en notas…')) ?>" aria-label="<?= e(t('Buscar en notas')) ?>">
        <select id="miasBook" aria-label="<?= e(t('Filtrar por libro')) ?>" hidden>
            <option value=""><?= e(t('Todos los libros')) ?></option>
        </select>
        <button type="button" id="miasMail">✉️ <?= e(t('Enviar por correo')) ?></button>
        <button type="button" id="miasExport">⬇ <?= e(t('Exportar')) ?></button>
        <button type="button" id="miasImportBtn">⬆ <?= e(t('Importar')) ?></button>
        <input type="file" id="miasImport" accept=".json,application/json" hidden>
    </div>

    <p id="miasCount" class="muted" role="status"></p>
    <div id="miasList"></div>
</div>
