// Sopa de Letras: toca la primera y la última letra de cada palabra escondida.
// Temas y palabras en games/sopa.json; la cuadrícula se arma en el cliente.
BFJ.define('sopa', function (el) {
    el.innerHTML = '<section class="card notice"><p>Cargando… ⏳</p></section>';
    BFJ.fetchBank('sopa.json').then(function (bank) {
        picker();

        var timed = false; // modo contrarreloj opcional

        function picker() {
            var html = '<div class="tr-cats">';
            for (var k in bank) {
                var t = bank[k];
                html += '<button type="button" class="tr-cat" data-cat="' + k + '">' +
                    '<span class="hs-cover">' + BFJ.omoji(t.icon) + '</span><strong>' + BFJ.esc(t.name) + '</strong>' +
                    '<small>' + t.words.length + ' palabras</small></button>';
            }
            el.innerHTML = html + '</div>' +
                '<p class="sp-mode"><button type="button" class="jbtn jbtn-ghost" id="spMode" aria-pressed="' + timed + '">' +
                (timed ? '⏱ Contrarreloj: <b>ON</b>' : '🐢 Tranquilo · activar ⏱') + '</button></p>' +
                '<p class="jh-note">Elige un tema y toca la <b>primera</b> y la <b>última</b> letra de cada palabra.' +
                (timed ? ' ¡Contra el reloj: 15 s por palabra, +2⭐ si terminas!' : '') + '</p>';
            document.getElementById('spMode').addEventListener('click', function () {
                timed = !timed;
                BFJ.snd('click');
                picker();
            });
            el.querySelectorAll('[data-cat]').forEach(function (b) {
                b.addEventListener('click', function () {
                    BFJ.snd('click');
                    start(b.getAttribute('data-cat'));
                });
            });
        }

        // Colores de marcador elegibles por el usuario (sólido + versión suave)
        var HLS = [
            { c: '#2f9e44', s: 'rgba(47,158,68,.30)' },
            { c: '#e8890c', s: 'rgba(232,137,12,.30)' },
            { c: '#d6336c', s: 'rgba(214,51,108,.22)' },
            { c: '#1971c2', s: 'rgba(25,113,194,.25)' },
            { c: '#9c36b5', s: 'rgba(156,54,181,.25)' }
        ];
        function hlGet() {
            try { var i = parseInt(localStorage.getItem('bf_sopa_hl') || '-1', 10);
                  return (i >= 0 && i < HLS.length) ? i : -1; } catch (e) { return -1; }
        }
        function hlSet(i) {
            try {
                if (i < 0) { localStorage.removeItem('bf_sopa_hl'); }
                else { localStorage.setItem('bf_sopa_hl', String(i)); }
            } catch (e) {}
        }

        function start(key) {
            var t = bank[key], S = t.size;
            try {
            var placed = buildAll(S, t.words);
            var words = placed.words;
            var grid = placed.grid;
            var left = words.slice(), found = 0, anchor = null, misses = 0, ended = false;
            var tmr = null;
            var hl = hlGet();

            el.innerHTML =
                '<div class="vf-qbox">' +
                '<div class="vf-prog"><span>' + BFJ.omoji(t.icon) + ' ' + BFJ.esc(t.name) + '</span>' +
                '<span class="sp-prog" aria-live="polite">0 / ' + words.length + '</span></div>' +
                (timed ? '<div class="jtimer" id="spt" aria-hidden="true"></div>' : '') +
                '<p class="sp-hint" id="spHint">🔍 Toca la <b>primera</b> letra de una palabra.</p>' +
                '<div class="sp-pal" role="group" aria-label="Color del marcador">' +
                '<span class="sp-pal-l">🎨 Marcador:</span>' +
                '<button type="button" class="sp-sw sp-auto' + (hl < 0 ? ' on' : '') +
                    '" data-hl="-1" aria-label="Color del tema" title="Color del tema">A</button>' +
                HLS.map(function (h, hi) {
                    return '<button type="button" class="sp-sw' + (hi === hl ? ' on' : '') +
                        '" data-hl="' + hi + '" style="--sw:' + h.c +
                        '" aria-label="Marcador ' + (hi + 1) + '"></button>';
                }).join('') + '</div>' +
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

            if (timed) {
                // 15 s por palabra; al agotarse, la ronda termina
                tmr = BFJ.timer(document.getElementById('spt'), words.length * 15, function () {
                    ended = true;
                    end(true);
                });
            }

            el.querySelectorAll('.sp-cell').forEach(function (cell) {
                cell.addEventListener('click', function () { tap(cell); });
            });

            // 🎨 Marcador personalizado: pinta encontradas, ancla y chips
            var qbox = el.querySelector('.vf-qbox');
            function applyHl(i) {
                hl = i;
                hlSet(i);
                if (i < 0) {
                    qbox.classList.remove('sp-custom');
                } else {
                    qbox.classList.add('sp-custom');
                    qbox.style.setProperty('--sp-hl', HLS[i].c);
                    qbox.style.setProperty('--sp-soft', HLS[i].s);
                }
                el.querySelectorAll('.sp-sw').forEach(function (b) {
                    var on = parseInt(b.getAttribute('data-hl'), 10) === i;
                    if (on) { b.classList.add('on'); } else { b.classList.remove('on'); }
                });
            }
            el.querySelectorAll('.sp-sw').forEach(function (b) {
                b.addEventListener('click', function () {
                    BFJ.snd('click');
                    applyHl(parseInt(b.getAttribute('data-hl'), 10));
                });
            });
            if (hl >= 0) { applyHl(hl); }

            function tap(cell) {
                if (ended) { return; }
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
                    if (!left.length) { ended = true; setTimeout(function () { end(false); }, 500); }
                } else {
                    misses++;
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

            var closed = false;
            function end(timedOut) {
                if (closed) { return; }
                closed = true;
                if (tmr) { tmr.stop(); }
                var flawless = misses === 0;
                var tBonus = timed && !timedOut ? 2 : 0; // venció el reloj
                var stars = Math.max(1,
                    (timedOut ? found : words.length) + (flawless && !timedOut ? 2 : 0) + tBonus);
                BFJ.celebrate({
                    slug: 'sopa', stars: stars,
                    emoji: timedOut ? '⏱' : (flawless ? '🏆' : '🔍'),
                    title: timedOut ? '¡Se acabó el tiempo!'
                        : (flawless ? '¡Vista de águila!' : '¡Todas encontradas!'),
                    perfect: !timedOut && flawless,
                    extra: found + ' de ' + words.length + ' palabras de "' + t.name + '"' +
                        (timedOut ? ' · ⏱ el reloj ganó esta vez'
                            : (flawless ? ' · ¡sin fallos! +2⭐'
                                : ' · ' + misses + ' fallo' + (misses === 1 ? '' : 's')) +
                              (tBonus ? ' · ⏱ ¡contrarreloj vencido! +2⭐' : '')),
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
