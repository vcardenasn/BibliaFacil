// US-172 — Completa el Versículo: texto real de la BD con palabra oculta.
// Niveles por bloques bíblicos: cada uno limita el mazo de versículos vía
// api/versiculo?set=… y se desbloquea al superar el anterior (BFJ.levels).
BFJ.define('versiculo', function (el) {
    var LEVELS = [
        { set: 'salmos',     icon: '🎵', name: 'Salmos',                desc: 'Cantos y oraciones',        n: 5, secs: 20, need: 3 },
        { set: 'sabiduria',  icon: '💡', name: 'Sabiduría',             desc: 'Proverbios, Job y más',     n: 6, secs: 18, need: 4 },
        { set: 'evangelios', icon: '✝️', name: 'Los Evangelios',        desc: 'La vida de Jesús',          n: 6, secs: 16, need: 4 },
        { set: 'cartas',     icon: '✉️', name: 'Las Cartas',            desc: 'Hechos y cartas del NT',    n: 7, secs: 15, need: 5 },
        { set: 'historia',   icon: '📜', name: 'Historias antiguas',    desc: 'De Génesis a Ester',        n: 7, secs: 14, need: 5 },
        { set: 'profetas',   icon: '🗣️', name: 'Profetas y revelación', desc: 'De Isaías a Apocalipsis',   n: 8, secs: 12, need: 6 },
        { set: 'biblia',     icon: '🌍', name: 'Toda la Biblia',        desc: 'El desafío final',          n: 8, secs: 14, need: 6 }
    ];

    function menu() {
        var done = BFJ.levels.passed('versiculo');
        var html = '<div class="vf-qbox"><h3 class="vj-ltitle">Elige tu nivel 📖</h3>' +
            '<p class="vj-lsub">Supera cada nivel para abrir el siguiente y ganar stickers 🎁</p>' +
            '<div class="vj-lgrid" role="list">' +
            LEVELS.map(function (L, ix) {
                var locked = ix > done;
                var best = BFJ.levels.best('versiculo', ix);
                return '<div role="listitem"><button type="button" class="vj-lv tr-in' +
                    (locked ? ' lock' : (ix < done ? ' done' : ' next')) + '"' +
                    (locked ? ' aria-disabled="true"' : '') + ' data-l="' + ix + '"' +
                    ' style="--d:' + ix * 55 + 'ms">' +
                    '<i aria-hidden="true">' + BFJ.omoji(locked ? '🔒' : L.icon) + '</i>' +
                    '<b>' + (ix + 1) + '. ' + L.name + '</b>' +
                    '<small>' + L.desc + ' · ' + L.n + ' versículos · pasas con ' + L.need + '</small>' +
                    '<span class="vj-lvs">' +
                    (locked ? 'Pasa el nivel anterior'
                        : (ix < done ? '✅ Superado · mejor ' + best + '/' + L.n
                            : '▶ ¡A jugar!')) +
                    '</span></button></div>';
            }).join('') + '</div></div>';
        el.innerHTML = html;
        el.querySelectorAll('.vj-lv:not(.lock)').forEach(function (b) {
            b.addEventListener('click', function () {
                BFJ.snd('click');
                play(parseInt(b.getAttribute('data-l'), 10));
            });
        });
    }

    function play(lix) {
        var L = LEVELS[lix];
        el.innerHTML = '<section class="card notice"><p>Cargando… ⏳</p></section>';
        fetch('/juegos/api/versiculo?n=' + L.n + '&set=' + L.set).then(function (r) {
            if (!r.ok) { throw new Error('api ' + r.status); }
            return r.json();
        }).then(function (res) {
            var qs = (res.qs || []).slice(0, L.n);
            if (!qs.length) { throw new Error('sin preguntas'); }
            var need = Math.min(L.need, qs.length); // por si el mazo viene corto
            var i = 0, ok = 0, streak = 0, t = null, locked = false;

            function render() {
                if (i >= qs.length) { return end(); }
                locked = false;
                var q = qs[i];
                el.innerHTML =
                    '<div class="vf-qbox">' +
                    '<div class="vf-prog"><span>' + BFJ.omoji(L.icon) + ' Nivel ' + (lix + 1) +
                        ' · ' + (i + 1) + ' / ' + qs.length + ' · pasas con ' + need + '</span>' +
                    '<span class="vf-streak' + (streak >= 3 ? ' hot' : '') + '">' +
                    (streak > 1 ? '🔥 x' + streak : '') + '</span></div>' +
                    '<div class="jtimer" id="vjt" aria-hidden="true"></div>' +
                    '<p class="vf-q vj-q">' + BFJ.esc(q.q) + '</p>' +
                    '<div class="tr-opts">' +
                    q.options.map(function (o, ix) {
                        return '<button type="button" class="jbtn tr-opt vj-opt tr-in" data-i="' + ix + '"' +
                            ' style="--d:' + ix * 70 + 'ms">' + BFJ.esc(o) + '</button>';
                    }).join('') +
                    '</div></div>';
                t = BFJ.timer(document.getElementById('vjt'), L.secs, function () { answer(-1); });
                el.querySelectorAll('.tr-opt').forEach(function (b) {
                    b.addEventListener('click', function () { answer(parseInt(b.getAttribute('data-i'), 10)); });
                });
            }

            function answer(ix) {
                if (locked) { return; }
                locked = true;
                if (t) { t.stop(); }
                var q = qs[i], hit = ix === q.a;
                el.querySelectorAll('.tr-opt').forEach(function (b) {
                    var bi = parseInt(b.getAttribute('data-i'), 10);
                    if (bi === q.a) { b.classList.add('ok'); }
                    else if (bi === ix) { b.classList.add('bad'); }
                    b.disabled = true;
                });
                if (hit) { ok++; streak++; BFJ.snd('ok'); BFJ.burst(el.querySelector('.tr-opt.ok')); }
                else { streak = 0; BFJ.snd('bad'); BFJ.shake(el.firstElementChild); }
                // Revela el versículo completo con la respuesta resaltada
                var rv = document.createElement('div');
                rv.className = 'vj-reveal bfj-pop';
                var fullTxt = BFJ.esc(q.full);
                var ans = q.options[q.a];
                if (ans && fullTxt.indexOf(BFJ.esc(ans)) >= 0) {
                    fullTxt = fullTxt.replace(BFJ.esc(ans), '<mark>' + BFJ.esc(ans) + '</mark>');
                }
                rv.innerHTML = '<p>' + fullTxt + '</p><em>' + BFJ.esc(q.ref) + '</em>';
                el.querySelector('.vf-qbox').appendChild(rv);
                i++;
                setTimeout(render, hit ? 1600 : 2200);
            }

            function end() {
                var won = ok >= need;
                if (won) { BFJ.levels.pass('versiculo', lix, ok); }
                var last = lix === LEVELS.length - 1;
                var emoji = won ? (last ? '🌍' : '🏆') : '💪';
                var title = won
                    ? (last ? '¡Biblia completa!' : '¡Nivel ' + (lix + 1) + ' superado!')
                    : '¡Casi lo logras!';
                var extra = ok + ' de ' + qs.length + ' versículos' +
                    (won
                        ? (last ? ' — ¡completaste todos los niveles!' : ' — se abrió el nivel ' + (lix + 2))
                        : ' — necesitas ' + need + ' para pasar');
                BFJ.celebrate({
                    slug: 'versiculo', stars: ok, emoji: emoji, title: title,
                    perfect: ok === qs.length, extra: extra,
                    againLabel: won && !last ? '▶ Nivel ' + (lix + 2) : '🔄 Reintentar',
                    onAgain: function () {
                        if (won && !last) { play(lix + 1); }
                        else if (!won) { play(lix); }
                        else { menu(); }
                    }
                });
            }

            render();
        }).catch(function () {
            el.innerHTML = '<section class="card notice"><p>No pude cargar el juego 😢</p>' +
                '<p><button type="button" class="jbtn jbtn-main" id="vjRetry">🔄 Reintentar</button></p></section>';
            document.getElementById('vjRetry').addEventListener('click', function () { play(lix); });
        });
    }

    menu();
});
