// US-173 — Trivia Bíblica: categorías, 10 preguntas, timer 15s, racha.
BFJ.define('trivia', function (el) {
    var CATS = {
        mezcla: { name: '¡Mezcla!', emoji: '🎲' },
        personajes: { name: 'Personajes', emoji: '🧔' },
        historias: { name: 'Historias', emoji: '📖' },
        milagros: { name: 'Milagros', emoji: '✨' },
        animales: { name: 'Animales', emoji: '🦁' }
    };
    el.innerHTML = '<section class="card notice"><p>Cargando… ⏳</p></section>';
    BFJ.fetchBank('trivia.json').then(function (bank) {
        catScreen();

        function catScreen() {
            var html = '<div class="tr-cats">';
            for (var k in CATS) {
                html += '<button type="button" class="tr-cat" data-cat="' + k + '">' +
                    '<span>' + CATS[k].emoji + '</span><strong>' + CATS[k].name + '</strong></button>';
            }
            el.innerHTML = html + '</div><p class="jh-note">Elige una categoría — ¡10 preguntas contra el reloj!</p>';
            el.querySelectorAll('[data-cat]').forEach(function (b) {
                b.addEventListener('click', function () {
                    BFJ.snd('click');
                    start(b.getAttribute('data-cat'));
                });
            });
        }

        function start(cat) {
            var pool = cat === 'mezcla' ? bank : bank.filter(function (q) { return q.cat === cat; });
            var qs = BFJ.pick(pool, 10);
            var i = 0, ok = 0, streak = 0, t = null, locked = false;

            function render() {
                if (i >= qs.length) { return end(); }
                locked = false;
                var q = qs[i];
                var opts = q.o.map(function (txt, ix) {
                    return '<button type="button" class="jbtn tr-opt" data-i="' + ix + '">' + BFJ.esc(txt) + '</button>';
                }).join('');
                el.innerHTML =
                    '<div class="vf-qbox">' +
                    '<div class="vf-prog"><span>' + CATS[cat].emoji + ' ' + (i + 1) + ' / ' + qs.length + '</span>' +
                    '<span class="vf-streak">' + (streak > 1 ? '🔥 x' + streak : '') + '</span></div>' +
                    '<div class="jtimer" id="trt" aria-hidden="true"></div>' +
                    '<p class="vf-q tr-q">' + BFJ.esc(q.q) + '</p>' +
                    '<div class="tr-opts">' + opts + '</div></div>';
                t = BFJ.timer(document.getElementById('trt'), 15, function () { answer(-1); });
                el.querySelectorAll('.tr-opt').forEach(function (b) {
                    b.addEventListener('click', function () { answer(parseInt(b.getAttribute('data-i'), 10)); });
                });
            }

            function answer(ix) {
                if (locked) { return; }
                locked = true;
                if (t) { t.stop(); }
                var q = qs[i];
                var hit = ix === q.a;
                el.querySelectorAll('.tr-opt').forEach(function (b) {
                    var bi = parseInt(b.getAttribute('data-i'), 10);
                    if (bi === q.a) { b.classList.add('ok'); }
                    else if (bi === ix) { b.classList.add('bad'); }
                    b.disabled = true;
                });
                if (hit) { ok++; streak++; BFJ.snd('ok'); }
                else { streak = 0; BFJ.snd('bad'); BFJ.shake(el.firstElementChild); }
                i++;
                setTimeout(render, hit ? 750 : 1600);
            }

            function end() {
                var emoji = ok >= 9 ? '🏆' : (ok >= 6 ? '🎉' : '💪');
                var title = ok >= 9 ? '¡Experto bíblico!' : (ok >= 6 ? '¡Muy bien!' : '¡Sigue practicando!');
                BFJ.celebrate({
                    slug: 'trivia', stars: ok, emoji: emoji, title: title, perfect: ok === qs.length,
                    extra: ok + ' de ' + qs.length + ' correctas · ' + CATS[cat].name,
                    onAgain: catScreen
                });
            }

            render();
        }
    }).catch(function () {
        el.innerHTML = '<section class="card notice"><p>No pude cargar el juego 😢 Intenta de nuevo.</p></section>';
    });
});
