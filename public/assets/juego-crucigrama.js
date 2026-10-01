// Crucigrama Bíblico: mini-crucigramas generados en games/crucigrama.json.
// Toca una casilla o una pista para elegir palabra; escribe y avanza solo.
BFJ.define('crucigrama', function (el) {
    el.innerHTML = '<section class="card notice"><p>Cargando… ⏳</p></section>';
    BFJ.fetchBank('crucigrama.json').then(function (puzzles) {
        picker();

        function picker() {
            var html = '<div class="tr-cats">';
            puzzles.forEach(function (p, ix) {
                html += '<button type="button" class="tr-cat" data-p="' + ix + '">' +
                    '<span class="hs-cover">' + BFJ.omoji(p.icon) + '</span><strong>' + BFJ.esc(p.name) + '</strong>' +
                    '<small>' + p.words.length + ' palabras</small></button>';
            });
            el.innerHTML = html + '</div>' +
                '<p class="jh-note">Elige un crucigrama — toca una casilla y escribe las letras.</p>';
            el.querySelectorAll('[data-p]').forEach(function (b) {
                b.addEventListener('click', function () {
                    BFJ.snd('click');
                    play(puzzles[+b.getAttribute('data-p')]);
                });
            });
        }

        function play(p) {
            try {
            // mapa de celdas: "r:c" → {ch, words:[w]}
            var cells = {}, starts = {};
            p.words.forEach(function (w) {
                w.cells = [];
                w.done = false;
                var dr = w.dir === 'h' ? 0 : 1, dc = w.dir === 'h' ? 1 : 0;
                for (var k = 0; k < w.w.length; k++) {
                    var r = w.r + dr * k, c = w.c + dc * k, key = r + ':' + c;
                    if (!cells[key]) { cells[key] = { ch: w.w[k], words: [] }; }
                    cells[key].words.push(w);
                    w.cells.push(key);
                }
                starts[w.r + ':' + w.c] = w.n;
            });

            var html = '<div class="vf-qbox">' +
                '<div class="vf-prog"><span>' + BFJ.omoji(p.icon) + ' ' + BFJ.esc(p.name) + '</span>' +
                '<span class="cg-prog" aria-live="polite">0 / ' + p.words.length + '</span></div>' +
                '<div class="cg-grid" style="--c:' + p.cols + '" aria-label="Crucigrama: ' + BFJ.esc(p.name) + '">';
            for (var r = 0; r < p.rows; r++) {
                for (var c = 0; c < p.cols; c++) {
                    var key = r + ':' + c, cell = cells[key];
                    if (!cell) {
                        html += '<span class="cg-block" aria-hidden="true"></span>';
                        continue;
                    }
                    html += '<label class="cg-box' + (starts[key] ? ' has-n' : '') + '">' +
                        (starts[key] ? '<i>' + starts[key] + '</i>' : '') +
                        '<input class="cg-cell" data-r="' + r + '" data-c="' + c + '"' +
                        ' maxlength="1" autocomplete="off" autocapitalize="characters" inputmode="text"' +
                        ' aria-label="Fila ' + (r + 1) + ', columna ' + (c + 1) + '"></label>';
                }
            }
            html += '</div>' +
                '<p class="cg-actions"><button type="button" class="jbtn jbtn-ghost" id="cgHint">' +
                '💡 Revelar letra <small>(−1⭐)</small></button></p>' +
                '<div class="cg-clues">' +
                clueList('Horizontales →', 'h') + clueList('Verticales ↓', 'v') +
                '</div></div>';
            el.innerHTML = html;

            var active = null; // palabra activa
            var done = 0, errs = 0, hints = 0;

            function clueList(title, dir) {
                var list = p.words.filter(function (w) { return w.dir === dir; });
                return '<div class="cg-col"><h4>' + title + '</h4>' +
                    list.map(function (w) {
                        return '<button type="button" class="cg-clue" data-n="' + w.n + '" data-dir="' + dir + '">' +
                            '<b>' + w.n + '.</b> ' + BFJ.esc(w.clue) +
                            ' <i>(' + w.w.length + ')</i></button>';
                    }).join('') + '</div>';
            }

            function inputOf(key) {
                var rc = key.split(':');
                return el.querySelector('.cg-cell[data-r="' + rc[0] + '"][data-c="' + rc[1] + '"]');
            }

            function select(w, dir) {
                active = w;
                el.querySelectorAll('.cg-box').forEach(function (b) { b.classList.remove('cg-sel'); });
                el.querySelectorAll('.cg-clue').forEach(function (b) { b.classList.remove('cg-on'); });
                if (!w) { return; }
                w.cells.forEach(function (key) {
                    var inp = inputOf(key);
                    if (inp) { inp.closest('.cg-box').classList.add('cg-sel'); }
                });
                var clue = el.querySelector('.cg-clue[data-n="' + w.n + '"][data-dir="' + w.dir + '"]');
                if (clue) { clue.classList.add('cg-on'); }
            }

            function focusCell(key) {
                var inp = inputOf(key);
                if (inp && !inp.readOnly) { inp.focus(); }
            }

            // índice de una celda dentro de la palabra
            function idxIn(w, key) { return w.cells.indexOf(key); }

            function checkWord(w) {
                var txt = w.cells.map(function (key) {
                    var inp = inputOf(key);
                    return inp ? inp.value.toUpperCase() : '';
                }).join('');
                if (txt !== w.w) {
                    errs++;
                    w.cells.forEach(function (key) {
                        var box = inputOf(key).closest('.cg-box');
                        box.classList.remove('cg-bad');
                        void box.offsetWidth;
                        box.classList.add('cg-bad');
                        setTimeout(function () { box.classList.remove('cg-bad'); }, 400);
                    });
                    BFJ.snd('bad');
                    return;
                }
                w.done = true;
                done++;
                w.cells.forEach(function (key) {
                    var inp = inputOf(key);
                    inp.readOnly = true;
                    inp.closest('.cg-box').classList.add('cg-ok');
                });
                var clue = el.querySelector('.cg-clue[data-n="' + w.n + '"][data-dir="' + w.dir + '"]');
                if (clue) { clue.classList.add('sp-done'); clue.disabled = true; }
                el.querySelector('.cg-prog').textContent = done + ' / ' + p.words.length;
                BFJ.snd('ok');
                BFJ.burst(inputOf(w.cells[0]).closest('.cg-box'));
                // siguiente palabra pendiente
                var next = p.words.filter(function (x) { return !x.done; })[0];
                if (!next) {
                    select(null);
                    setTimeout(end, 500);
                } else {
                    select(next);
                    focusEditable(next);
                }
            }

            // enfoca la primera celda editable vacía de la palabra
            function focusEditable(w) {
                var key = w.cells.filter(function (k) {
                    var inp = inputOf(k);
                    return !inp.readOnly && !inp.value;
                })[0] || w.cells.filter(function (k) {
                    return !inputOf(k).readOnly;
                })[0];
                if (key) { focusCell(key); }
            }

            var lastKey = null; // última celda tocada por el usuario (toggle de dirección)
            el.querySelectorAll('.cg-cell').forEach(function (inp) {
                var key = inp.getAttribute('data-r') + ':' + inp.getAttribute('data-c');
                inp.addEventListener('focus', function () {
                    var ws = cells[key].words.filter(function (w) { return !w.done; });
                    if (!ws.length) { ws = cells[key].words; }
                    if (active && ws.indexOf(active) >= 0) { return; } // conserva dirección
                    if (ws.length) { select(ws[0]); }
                });
                inp.addEventListener('click', function () {
                    // segundo toque sobre celda compartida → alterna horizontal/vertical
                    var ws = cells[key].words.filter(function (w) { return !w.done; });
                    if (lastKey === key && ws.length > 1) {
                        select(ws.indexOf(active) === 0 ? ws[1] : ws[0]);
                    }
                    lastKey = key;
                });
                inp.addEventListener('input', function () {
                    inp.value = (inp.value.normalize ? inp.value.normalize('NFD') : inp.value)
                        .replace(/[̀-ͯ]/g, '').toUpperCase().replace(/[^A-ZÑ]/g, '').slice(0, 1);
                    if (!inp.value || !active) { return; }
                    var i = idxIn(active, key);
                    for (var j = i + 1; j < active.cells.length; j++) {
                        if (!inputOf(active.cells[j]).readOnly) {
                            focusCell(active.cells[j]);
                            break;
                        }
                    }
                    // ¿palabra llena? → valida
                    var filled = active.cells.every(function (k) { return inputOf(k).value; });
                    if (filled) { checkWord(active); }
                });
                inp.addEventListener('keydown', function (ev) {
                    if (ev.key !== 'Backspace' || inp.value || !active) { return; }
                    var i = idxIn(active, key);
                    for (var j = i - 1; j >= 0; j--) {
                        var prev = inputOf(active.cells[j]);
                        if (!prev.readOnly) {
                            prev.value = '';
                            prev.focus();
                            break;
                        }
                    }
                    ev.preventDefault();
                });
            });

            // 💡 Revelar letra: rellena la primera celda vacía de la palabra
            // activa (cuesta ⭐ al final). La letra revelada queda fija.
            document.getElementById('cgHint').addEventListener('click', function () {
                var w = (active && !active.done) ? active
                    : p.words.filter(function (x) { return !x.done; })[0];
                if (!w) { return; }
                select(w);
                var empty = w.cells.filter(function (k) {
                    var inp = inputOf(k);
                    return !inp.readOnly && !inp.value;
                })[0];
                if (!empty) { return; }
                var inp = inputOf(empty);
                inp.value = cells[empty].ch;
                inp.readOnly = true;
                inp.closest('.cg-box').classList.add('cg-hint');
                hints++;
                BFJ.snd('click');
                var filled = w.cells.every(function (k) { return inputOf(k).value; });
                if (filled) { checkWord(w); }
                else { focusEditable(w); }
            });

            el.querySelectorAll('.cg-clue').forEach(function (b) {
                b.addEventListener('click', function () {
                    var w = p.words.filter(function (x) {
                        return x.n === +b.getAttribute('data-n') && x.dir === b.getAttribute('data-dir');
                    })[0];
                    if (!w || w.done) { return; }
                    BFJ.snd('click');
                    select(w);
                    focusEditable(w);
                });
            });

            function end() {
                var flawless = errs === 0 && hints === 0;
                var stars = Math.max(1, p.words.length + (flawless ? 2 : 0) - hints);
                BFJ.celebrate({
                    slug: 'crucigrama', stars: stars,
                    emoji: flawless ? '🏆' : '🧩',
                    title: flawless ? '¡Sin una sola falla!' : '¡Crucigrama resuelto!',
                    perfect: flawless,
                    extra: p.words.length + ' palabras de "' + p.name + '"' +
                        (flawless ? ' · ¡perfecto! +2⭐'
                            : (errs ? ' · ' + errs + ' fallo' + (errs === 1 ? '' : 's') : '') +
                              (hints ? ' · ' + hints + ' pista' + (hints === 1 ? '' : 's') + ' 💡' : '')),
                    againLabel: '🔁 Otro crucigrama',
                    onAgain: picker
                });
            }
            } catch (e) {
                el.innerHTML = '<section class="card notice"><p>No pude armar el crucigrama 😢 Intenta de nuevo.</p></section>';
            }
        }
    }).catch(function () {
        el.innerHTML = '<section class="card notice"><p>No pude cargar el juego 😢 Intenta de nuevo.</p></section>';
    });
});
