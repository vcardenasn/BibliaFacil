// US-172 — Completa el Versículo: texto real de la BD con palabra oculta.
BFJ.define('versiculo', function (el) {
    el.innerHTML = '<section class="card notice"><p>Cargando… ⏳</p></section>';
    fetch('api/versiculo?n=10').then(function (r) {
        if (!r.ok) { throw new Error('api ' + r.status); }
        return r.json();
    }).then(function (res) {
        var qs = (res.qs || []).slice(0, 10);
        if (!qs.length) { throw new Error('sin preguntas'); }
        var i = 0, ok = 0, streak = 0, t = null, locked = false;

        function render() {
            if (i >= qs.length) { return end(); }
            locked = false;
            var q = qs[i];
            el.innerHTML =
                '<div class="vf-qbox">' +
                '<div class="vf-prog"><span>📖 ' + (i + 1) + ' / ' + qs.length + '</span>' +
                '<span class="vf-streak">' + (streak > 1 ? '🔥 x' + streak : '') + '</span></div>' +
                '<div class="jtimer" id="vjt" aria-hidden="true"></div>' +
                '<p class="vf-q vj-q">' + BFJ.esc(q.q) + '</p>' +
                '<div class="tr-opts">' +
                q.options.map(function (o, ix) {
                    return '<button type="button" class="jbtn tr-opt vj-opt" data-i="' + ix + '">' + BFJ.esc(o) + '</button>';
                }).join('') +
                '</div></div>';
            t = BFJ.timer(document.getElementById('vjt'), 15, function () { answer(-1); });
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
            if (hit) { ok++; streak++; BFJ.snd('ok'); }
            else { streak = 0; BFJ.snd('bad'); BFJ.shake(el.firstElementChild); }
            // Revela el versículo completo con referencia
            var rv = document.createElement('div');
            rv.className = 'vj-reveal bfj-pop';
            rv.innerHTML = '<p>' + BFJ.esc(q.full) + '</p><em>' + BFJ.esc(q.ref) + '</em>';
            el.querySelector('.vf-qbox').appendChild(rv);
            i++;
            setTimeout(render, hit ? 1600 : 2200);
        }

        function end() {
            var emoji = ok >= 9 ? '🏆' : (ok >= 6 ? '📖' : '💪');
            var title = ok >= 9 ? '¡Memorizas la Palabra!' : (ok >= 6 ? '¡Muy bien!' : '¡Sigue leyendo!');
            BFJ.celebrate({
                slug: 'versiculo', stars: ok, emoji: emoji, title: title, perfect: ok === qs.length,
                extra: ok + ' de ' + qs.length + ' versículos completados',
                onAgain: function () { location.reload(); }
            });
        }

        render();
    }).catch(function () {
        el.innerHTML = '<section class="card notice"><p>No pude cargar el juego 😢 Intenta de nuevo.</p></section>';
    });
});
