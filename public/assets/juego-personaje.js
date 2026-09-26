// US-178 — Adivina el Personaje: 3 pistas progresivas, menos pistas = más ⭐.
// Ronda de 5 personajes: pista 1 = 3⭐, pista 2 = 2⭐, pista 3 = 1⭐.
BFJ.define('personaje', function (el) {
    el.innerHTML = '<section class="card notice"><p>Cargando… ⏳</p></section>';
    BFJ.fetchBank('personajes.json').then(function (bank) {
        var round = BFJ.pick(bank, 5);
        var i = 0, total = 0, clue = 0, locked = false, gone = [];

        var opts = [];
        function render() {
            if (i >= round.length) { return end(); }
            locked = false;
            clue = 0;
            gone = [];
            var p0 = round[i];
            opts = BFJ.pick(
                bank.filter(function (x) { return x.name !== p0.name; }), 3
            ).map(function (x) { return x.name; });
            opts.push(p0.name);
            BFJ.shuffle(opts);
            draw();
        }

        function draw() {
            var p = round[i];

            el.innerHTML =
                '<div class="vf-qbox" id="pbox">' +
                '<div class="vf-prog"><span>🔍 Personaje ' + (i + 1) + ' / ' + round.length + '</span>' +
                '<span class="vf-streak">⭐ ' + total + '</span></div>' +
                '<div class="pj-clues">' +
                p.clues.slice(0, clue + 1).map(function (c, ci) {
                    return '<div class="pj-clue bfj-pop"><em>Pista ' + (ci + 1) + '</em>' + BFJ.esc(c) + '</div>';
                }).join('') +
                '</div>' +
                '<div class="tr-opts" id="popts">' +
                opts.map(function (o) {
                    return '<button type="button" class="jbtn tr-opt" data-n="' + BFJ.esc(o) + '"' +
                        (gone.indexOf(o) >= 0 ? ' disabled' : '') + '>' + BFJ.esc(o) + '</button>';
                }).join('') +
                '</div>' +
                (clue < 2
                    ? '<button type="button" class="jbtn jbtn-ghost pj-more" id="pjMore">🔍 Otra pista (−⭐)</button>'
                    : '') +
                '</div>';

            el.querySelectorAll('#popts .tr-opt').forEach(function (b) {
                b.addEventListener('click', function () { guess(b.getAttribute('data-n'), b); });
            });
            var more = document.getElementById('pjMore');
            if (more) {
                more.addEventListener('click', function () {
                    BFJ.snd('click');
                    clue++;
                    draw();
                });
            }
        }

        function guess(name, btn) {
            if (locked) { return; }
            var p = round[i];
            if (name === p.name) {
                locked = true;
                var stars = 3 - clue;
                total += stars;
                BFJ.snd('ok');
                btn.classList.add('ok');
                var pbox = document.getElementById('pbox');
                pbox.insertAdjacentHTML('beforeend',
                    '<div class="pj-reveal bfj-pop"><span>' + p.emoji + '</span><strong>¡Es ' + BFJ.esc(p.name) + '!</strong>' +
                    '<em>+' + stars + '⭐</em></div>');
                i++;
                setTimeout(render, 1500);
            } else {
                BFJ.snd('bad');
                gone.push(name);
                btn.disabled = true;
                btn.classList.add('bad');
                BFJ.shake(btn);
                if (clue < 2) {
                    clue++;
                    setTimeout(draw, 450);
                } else {
                    // Sin más pistas: revela y sigue
                    locked = true;
                    var pbox2 = document.getElementById('pbox');
                    pbox2.insertAdjacentHTML('beforeend',
                        '<div class="pj-reveal bfj-pop"><span>' + p.emoji + '</span><strong>Era ' + BFJ.esc(p.name) + '</strong><em>+0⭐</em></div>');
                    i++;
                    setTimeout(render, 1800);
                }
            }
        }

        function end() {
            var emoji = total >= 13 ? '🏆' : (total >= 8 ? '🎉' : '🔍');
            var title = total >= 13 ? '¡Detective bíblico!' : (total >= 8 ? '¡Muy buenas pistas!' : '¡Sigue intentando!');
            BFJ.celebrate({
                slug: 'personaje', stars: total, emoji: emoji, title: title, perfect: total >= 15,
                extra: total + '⭐ de 15 posibles',
                onAgain: function () { location.reload(); }
            });
        }

        render();
    }).catch(function () {
        el.innerHTML = '<section class="card notice"><p>No pude cargar el juego 😢 Intenta de nuevo.</p></section>';
    });
});
