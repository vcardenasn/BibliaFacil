// US-177 — ¿Verdadero o Falso? 10 afirmaciones, timer de 12s, racha con fuego.
BFJ.define('vf', function (el) {
    el.innerHTML = '<section class="card notice"><p>' + BFJ.T('Cargando… ⏳') + '</p></section>';
    BFJ.fetchBank('vf.json').then(function (bank) {
        round();

        function round() {
        var qs = BFJ.pick(bank, 10);
        var i = 0, ok = 0, streak = 0, bestStreak = 0, fast = 0, t = null, locked = false;

        function render() {
            if (i >= qs.length) { return end(); }
            locked = false;
            var q = qs[i];
            el.innerHTML =
                '<div class="vf-qbox" id="vfbox">' +
                '<div class="vf-prog"><span>' + BFJ.T('Pregunta') + ' ' + (i + 1) + ' / ' + qs.length + '</span>' +
                '<span class="vf-streak' + (streak >= 3 ? ' hot' : '') + '">' +
                (streak > 1 ? '🔥 x' + streak : '') + '</span></div>' +
                '<div class="jtimer" id="vftimer" aria-hidden="true"></div>' +
                '<p class="vf-q">' + BFJ.esc(q.t) + '</p>' +
                '<div class="vf-btns">' +
                '<button type="button" class="jbtn" data-a="1">✓<small>' + BFJ.T('VERDADERO') + '</small></button>' +
                '<button type="button" class="jbtn" data-a="0">✗<small>' + BFJ.T('FALSO') + '</small></button>' +
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
                if (streak > bestStreak) { bestStreak = streak; }
                if (t && t.left() > 8) { // <4s tras aparecer = ⚡ rápida
                    fast++;
                    var fx = document.createElement('span');
                    fx.className = 'vf-fast bfj-pop';
                    fx.textContent = BFJ.T('⚡ ¡rápida!');
                    box.appendChild(fx);
                }
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
            // Racha premiada (+1⭐/5 seguidas) y rapidez (+1⭐/3 con ⚡)
            var bonus = Math.floor(bestStreak / 5);
            var fastBonus = Math.floor(fast / 3);
            var stars = ok + bonus + fastBonus;
            var emoji = ok >= 9 ? '🏆' : (ok >= 6 ? '🎉' : '💪');
            var title = ok >= 9 ? BFJ.T('¡Eres un campeón!') : (ok >= 6 ? BFJ.T('¡Muy bien!') : BFJ.T('¡Sigue practicando!'));
            BFJ.celebrate({
                slug: 'vf', stars: stars, emoji: emoji, title: title, perfect: ok === qs.length,
                extra: ok + ' ' + BFJ.T('de') + ' ' + qs.length + ' ' + BFJ.T('correctas') +
                    (bestStreak >= 3 ? ' · ' + BFJ.T('racha máx') + ' 🔥x' + bestStreak : '') +
                    (fast ? ' · ⚡x' + fast + ' ' + BFJ.T('rápidas') : '') +
                    (bonus + fastBonus ? ' · +' + (bonus + fastBonus) + '⭐ ' + BFJ.T('bonus') : ''),
                onAgain: round // reinicio sin recargar la página
            });
        }

        render();
        }
    }).catch(function () {
        el.innerHTML = '<section class="card notice"><p>' + BFJ.T('No pude cargar el juego 😢 Intenta de nuevo.') + '</p></section>';
    });
});
