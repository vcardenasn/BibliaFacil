// US-177 — ¿Verdadero o Falso? 10 afirmaciones, timer de 12s, racha con fuego.
BFJ.define('vf', function (el) {
    el.innerHTML = '<section class="card notice"><p>Cargando… ⏳</p></section>';
    BFJ.fetchBank('vf.json').then(function (bank) {
        var qs = BFJ.pick(bank, 10);
        var i = 0, ok = 0, streak = 0, t = null, locked = false;

        function render() {
            if (i >= qs.length) { return end(); }
            locked = false;
            var q = qs[i];
            el.innerHTML =
                '<div class="vf-qbox" id="vfbox">' +
                '<div class="vf-prog"><span>Pregunta ' + (i + 1) + ' / ' + qs.length + '</span>' +
                '<span class="vf-streak">' + (streak > 1 ? '🔥 x' + streak : '') + '</span></div>' +
                '<div class="jtimer" id="vftimer" aria-hidden="true"></div>' +
                '<p class="vf-q">' + BFJ.esc(q.t) + '</p>' +
                '<div class="vf-btns">' +
                '<button type="button" class="jbtn" data-a="1">✓<small>VERDADERO</small></button>' +
                '<button type="button" class="jbtn" data-a="0">✗<small>FALSO</small></button>' +
                '</div></div>';
            t = BFJ.timer(document.getElementById('vftimer'), 12, function () { answer(null); });
            el.querySelectorAll('[data-a]').forEach(function (b) {
                b.addEventListener('click', function () { answer(b.getAttribute('data-a') === '1'); });
            });
        }

        function answer(guess) {
            if (locked) { return; }
            locked = true;
            if (t) { t.stop(); }
            var q = qs[i], box = document.getElementById('vfbox');
            var hit = guess === q.a;
            if (hit) {
                ok++; streak++;
                BFJ.snd('ok');
                box.classList.add('vf-ok');
                BFJ.pop(box);
            } else {
                streak = 0;
                BFJ.snd('bad');
                box.classList.add('vf-bad');
                BFJ.shake(box);
            }
            if (q.note) {
                var n = document.createElement('p');
                n.className = 'vf-note';
                n.textContent = '💡 ' + q.note;
                box.appendChild(n);
            }
            i++;
            setTimeout(render, hit ? 700 : 1400);
        }

        function end() {
            var stars = ok; // 1⭐ por acierto (máx 10)
            var emoji = stars >= 9 ? '🏆' : (stars >= 6 ? '🎉' : '💪');
            var title = stars >= 9 ? '¡Eres un campeón!' : (stars >= 6 ? '¡Muy bien!' : '¡Sigue practicando!');
            BFJ.celebrate({
                slug: 'vf', stars: stars, emoji: emoji, title: title,
                extra: ok + ' de ' + qs.length + ' correctas',
                onAgain: function () { location.reload(); }
            });
        }

        render();
    }).catch(function () {
        el.innerHTML = '<section class="card notice"><p>No pude cargar el juego 😢 Intenta de nuevo.</p></section>';
    });
});
