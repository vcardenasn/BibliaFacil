<h1>Mis anotaciones</h1>
<p class="muted">Resaltados, notas y favoritos — se guardan en este dispositivo.</p>

<div class="streak" id="streakBox" hidden></div>

<div id="miasApp">
    <div class="mias-chips" role="tablist">
        <button type="button" data-f="all" class="on">Todas</button>
        <button type="button" data-f="hl">Resaltadas</button>
        <button type="button" data-f="note">Con nota</button>
        <button type="button" data-f="fav">Favoritas</button>
    </div>

    <div class="mias-tools">
        <input type="search" id="miasQ" placeholder="Buscar en notas…" aria-label="Buscar en notas">
        <button type="button" id="miasExport">⬇ Exportar</button>
        <label for="miasImport">⬆ Importar<input type="file" id="miasImport" accept=".json,application/json" hidden></label>
    </div>

    <div id="miasList"></div>
</div>
