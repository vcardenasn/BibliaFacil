// Sopa de Letras: toca la primera y la última letra de cada palabra escondida.
// Temas y palabras en games/sopa.json; la cuadrícula se arma en el cliente.
BFJ.define('sopa', function (el) {
    el.innerHTML = '<section class="card notice"><p>Cargando… ⏳</p></section>';
    BFJ.fetchBank('sopa.json').then(function (bank) {
        picker();

        function picker() {
            var html = '<div class="tr-cats">';
            for (var k in bank) {
                var t = bank[k];
                html += '<button type="button" class="tr-cat" data-cat="' + k + '">' +
                    '<span class="hs-cover">' + BFJ.omoji(t.icon) + '</span><strong>' + BFJ.esc(t.name) + '</strong>' +
                    '<small>' + t.words.length + ' palabras</small></button>';
            }
            el.innerHTML = html + '</div>' +
                '<p class="jh-note">Elige un tema y toca la <b>primera</b> y la <b>última</b> letra de cada palabra.</p>';
            el.querySelectorAll('[data-cat]').forEach(function (b) {
                b.addEventListener('click', function () {
                    BFJ.snd('click');
                    start(b.getAttribute('data-cat'));
                });
            });
        }

        function start(key) {
            var t = bank[key], S = t.size;
            try {
            var placed = buildAll(S, t.words);
            var words = placed.words;
            var grid = placed.grid;
            var left = words.slice(), found = 0, anchor = null;

            el.innerHTML =
                '<div class="vf-qbox">' +
                '<div class="vf-prog"><span>' + BFJ.omoji(t.icon) + ' ' + BFJ.esc(t.name) + '</span>' +
                '<span class="sp-prog" aria-live="polite">0 / ' + words.length + '</span></div>' +
                '<p class="sp-hint" id="spHint">🔍 Toca la <b>primera</b> letra de una palabra.</p>' +
                '<div class="sp-grid" aria-label="Sopa de letras: ' + BFJ.esc(t.name) + '"' +
                ' style="--n:' + S + '">' +
                grid.map(function (row, r) {
                    return row.map(function (ch, c) {
                        return '<button type="button" class="sp-cell" data-r="' + r +
                            '" data-c="' + c + '" aria-label="Letra ' + ch + ', fila ' + (r + 1) +
                            ', columna ' + (c + 1) + '">' + ch + '</button>';
                    }).join('');
                }).join('') +
                '</div>' +
                '<div class="sp-words">' +
                words.map(function (w) {
                    return '<span class="sp-word" data-w="' + w + '">' + w + '</span>';
                }).join('') +
                '</div></div>';
            var hint = document.getElementById('spHint');

            el.querySelectorAll('.sp-cell').forEach(function (cell) {
                cell.addEventListener('click', function () { tap(cell); });
            });

            function tap(cell) {
                if (!anchor) {
                    anchor = cell;
                    cell.classList.add('sp-anchor');
                    hint.innerHTML = '👆 Ahora toca la <b>última</b> letra (en línea recta).';
                    BFJ.snd('click');
                    return;
                }
                if (cell === anchor) { // segundo toque = cancelar
                    anchor = null;
                    cell.classList.remove('sp-anchor');
                    hint.innerHTML = '🔍 Toca la <b>primera</b> letra de una palabra.';
                    return;
                }
                var cells = line(anchor, cell);
                var txt = cells.map(function (cc) { return cc.textContent; }).join('');
                var rev = txt.split('').reverse().join('');
                var hit = left.indexOf(txt) >= 0 ? txt : (left.indexOf(rev) >= 0 ? rev : null);
                anchor.classList.remove('sp-anchor');
                anchor = null;
                if (hit) {
                    left.splice(left.indexOf(hit), 1);
                    found++;
                    cells.forEach(function (cc) { cc.classList.add('sp-found'); });
                    var chip = el.querySelector('.sp-word[data-w="' + hit + '"]');
                    if (chip) { chip.classList.add('sp-done'); }
                    el.querySelector('.sp-prog').textContent = found + ' / ' + words.length;
                    hint.textContent = '✅ ¡' + hit + '! Quedan ' + left.length + ' palabras.';
                    BFJ.snd('ok');
                    BFJ.burst(cells[cells.length - 1]);
                    if (!left.length) { setTimeout(end, 500); }
                } else {
                    BFJ.snd('bad');
                    cells.forEach(function (cc) {
                        cc.classList.remove('sp-miss');
                        void cc.offsetWidth;
                        cc.classList.add('sp-miss');
                        setTimeout(function () { cc.classList.remove('sp-miss'); }, 400);
                    });
                    hint.innerHTML = '🔍 Intenta otra — ¡en <b>línea recta</b>!';
                }
            }

            // Celdas sobre la línea ancla→fin; si no es recta, devuelve ambas puntas
            function line(a, b) {
                var r1 = +a.getAttribute('data-r'), c1 = +a.getAttribute('data-c');
                var r2 = +b.getAttribute('data-r'), c2 = +b.getAttribute('data-c');
                var dr = r2 - r1, dc = c2 - c1;
                var sr = dr === 0 ? 0 : dr / Math.abs(dr);
                var sc = dc === 0 ? 0 : dc / Math.abs(dc);
                var cells = [a];
                if (!(dr === 0 || dc === 0 || Math.abs(dr) === Math.abs(dc))) {
                    return [a, b];
                }
                var r = r1 + sr, c = c1 + sc;
                while (r !== r2 || c !== c2) {
                    cells.push(el.querySelector('[data-r="' + r + '"][data-c="' + c + '"]'));
                    r += sr; c += sc;
                }
                cells.push(b);
                return cells;
            }

            function end() {
                BFJ.celebrate({
                    slug: 'sopa', stars: words.length, emoji: '🔍',
                    title: '¡Todas encontradas!', perfect: true,
                    extra: words.length + ' palabras de "' + t.name + '"',
                    againLabel: '🔁 Otro tema',
                    onAgain: picker
                });
            }
            } catch (e) {
                el.innerHTML = '<section class="card notice"><p>No pude armar la sopa 😢 Intenta de nuevo.</p></section>';
            }
        }

        // Intenta armar la cuadrícula completa; si alguna palabra no cabe
        // tras varios intentos, se retira del tablero (la ronda sigue ganable).
        function buildAll(size, words) {
            var list = words.slice(), res = null;
            for (var att = 0; att < 25; att++) {
                res = build(size, list);
                if (!res.missing.length) { break; }
            }
            if (res.missing.length) {
                list = list.filter(function (w) { return res.missing.indexOf(w) < 0; });
            }
            return { grid: res.grid, words: list };
        }

        // Coloca las palabras (→, ↓, ↘) y rellena con letras al azar
        function build(size, words) {
            var g = [], missing = [];
            for (var r = 0; r < size; r++) { g.push(new Array(size).fill(null)); }
            var dirs = [[0, 1], [1, 0], [1, 1]];
            words.forEach(function (w) {
                var placed = false, tries = 0;
                while (!placed && tries++ < 400) {
                    var d = dirs[Math.floor(Math.random() * dirs.length)];
                    var r = Math.floor(Math.random() * size), c = Math.floor(Math.random() * size);
                    var er = r + d[0] * (w.length - 1), ec = c + d[1] * (w.length - 1);
                    if (er >= size || ec >= size) { continue; }
                    var ok = true;
                    for (var k = 0; k < w.length; k++) {
                        var cell = g[r + d[0] * k][c + d[1] * k];
                        if (cell && cell !== w[k]) { ok = false; break; }
                    }
                    if (!ok) { continue; }
                    for (k = 0; k < w.length; k++) { g[r + d[0] * k][c + d[1] * k] = w[k]; }
                    placed = true;
                }
                if (!placed) { missing.push(w); }
            });
            var ABC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            for (r = 0; r < size; r++) {
                for (var c = 0; c < size; c++) {
                    if (!g[r][c]) { g[r][c] = ABC[Math.floor(Math.random() * 26)]; }
                }
            }
            return { grid: g, missing: missing };
        }
    }).catch(function () {
        el.innerHTML = '<section class="card notice"><p>No pude cargar el juego 😢 Intenta de nuevo.</p></section>';
    });
});
