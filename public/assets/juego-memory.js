// US-175 — Memory Bíblico: 8 parejas personaje↔hazaña, grid 4×4, flip 3D.
BFJ.define('memory', function (el) {
    el.innerHTML = '<section class="card notice"><p>Cargando… ⏳</p></section>';
    BFJ.fetchBank('memory.json').then(function (bank) {
        var pairs = BFJ.pick(bank, 8);
        var cards = [];
        pairs.forEach(function (p, pi) {
            cards.push({ pid: pi, emoji: p.a });
            cards.push({ pid: pi, emoji: p.b });
        });
        BFJ.shuffle(cards);

        var open = [], matched = 0, moves = 0, lock = false;
        var foundNames = [];

        el.innerHTML =
            '<div class="vf-prog"><span>Parejas: <strong id="mmOk">0</strong> / ' + pairs.length + '</span>' +
            '<span>Movimientos: <strong id="mmMv">0</strong></span></div>' +
            '<div class="mm-grid">' + cards.map(function (c, i) {
                return '<button type="button" class="mm-card" data-i="' + i + '" aria-label="Carta">' +
                    '<span class="mm-face mm-back">✝</span>' +
                    '<span class="mm-face mm-front">' + c.emoji + '</span></button>';
            }).join('') + '</div>' +
            '<div class="mm-found" id="mmFound"></div>';

        el.querySelectorAll('.mm-card').forEach(function (b) {
            b.addEventListener('click', function () { flip(parseInt(b.getAttribute('data-i'), 10), b); });
        });

        function flip(i, btn) {
            if (lock || btn.classList.contains('open') || btn.classList.contains('done')) { return; }
            BFJ.snd('click');
            btn.classList.add('open');
            open.push({ i: i, btn: btn });
            if (open.length < 2) { return; }

            moves++;
            document.getElementById('mmMv').textContent = moves;
            var a = open[0], b = open[1];
            lock = true;

            if (cards[a.i].pid === cards[b.i].pid) {
                setTimeout(function () {
                    a.btn.classList.add('done'); b.btn.classList.add('done');
                    matched++;
                    document.getElementById('mmOk').textContent = matched;
                    BFJ.snd('ok');
                    var name = pairs[cards[a.i].pid].name;
                    foundNames.push(name);
                    var f = document.getElementById('mmFound');
                    f.innerHTML += '<span class="mm-tag">' + BFJ.esc(name) + '</span>';
                    open = []; lock = false;
                    if (matched === pairs.length) { setTimeout(end, 600); }
                }, 380);
            } else {
                BFJ.snd('bad');
                setTimeout(function () {
                    a.btn.classList.remove('open'); b.btn.classList.remove('open');
                    open = []; lock = false;
                }, 750);
            }
        }

        function end() {
            var stars = moves <= 10 ? 5 : (moves <= 14 ? 4 : (moves <= 18 ? 3 : (moves <= 24 ? 2 : 1)));
            BFJ.celebrate({
                slug: 'memory', stars: stars, perfect: moves <= 10,
                emoji: moves <= 10 ? '🏆' : '🎉',
                title: moves <= 10 ? '¡Memoria de campeón!' : '¡Completaste el memory!',
                extra: 'Encontraste ' + pairs.length + ' parejas en ' + moves + ' movimientos',
                onAgain: function () { location.reload(); }
            });
        }
    }).catch(function () {
        el.innerHTML = '<section class="card notice"><p>No pude cargar el juego 😢 Intenta de nuevo.</p></section>';
    });
});
