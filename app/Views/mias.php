<h1>Mis anotaciones</h1>
<p class="muted">Resaltados, notas y favoritos — se guardan en este dispositivo.</p>

<div class="streak" id="streakBox" hidden></div>

<div id="miasApp">
    <div class="mias-chips" role="radiogroup" aria-label="Filtrar por tipo de anotación">
        <button type="button" data-f="all" class="on" role="radio" aria-checked="true" tabindex="0">Todas</button>
        <button type="button" data-f="hl" role="radio" aria-checked="false" tabindex="-1">Resaltadas</button>
        <button type="button" data-f="note" role="radio" aria-checked="false" tabindex="-1">Con nota</button>
        <button type="button" data-f="fav" role="radio" aria-checked="false" tabindex="-1">Favoritas</button>
    </div>

    <div class="mias-tools">
        <input type="search" id="miasQ" placeholder="Buscar en notas…" aria-label="Buscar en notas">
        <select id="miasBook" aria-label="Filtrar por libro" hidden>
            <option value="">Todos los libros</option>
        </select>
        <button type="button" id="miasExport">⬇ Exportar</button>
        <button type="button" id="miasImportBtn">⬆ Importar</button>
        <input type="file" id="miasImport" accept=".json,application/json" hidden>
    </div>

    <p id="miasCount" class="muted" role="status"></p>
    <div id="miasList"></div>
</div>
