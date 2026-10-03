// US-175 — Memory Bíblico: 8 parejas personaje↔hazaña, grid 4×4, flip 3D.
BFJ.define('memory', function (el) {
    el.innerHTML = '<section class="card notice"><p>' + BFJ.T('Cargando… ⏳') + '</p></section>';
    BFJ.fetchBank('memory.json').then(function (bank) {
        round();

        function round() {
        var pairs = BFJ.pick(bank, 8);
        var cards = [];
        pairs.forEach(function (p, pi) {
            cards.push({ pid: pi, emoji: p.a, img: p.ia || '', label: p.na || p.name });
            cards.push({ pid: pi, emoji: p.b, img: p.ib || '', label: p.nb || p.name });
        });
        BFJ.shuffle(cards);

        var open = [], matched = 0, moves = 0, lock = false;
        var foundNames = [];

        el.innerHTML =
            '<div class="vf-prog"><span>' + BFJ.T('Parejas') + ': <strong id="mmOk">0</strong> / ' + pairs.length + '</span>' +
            '<span>' + BFJ.T('Movimientos') + ': <strong id="mmMv">0</strong></span></div>' +
            '<div class="mm-grid">' + cards.map(function (c, i) {
                return '<button type="button" class="mm-card" data-i="' + i +
                    '" aria-label="' + BFJ.T('Carta') + ' ' + (i + 1) + ' ' + BFJ.T('de') + ' ' + cards.length + '">' +
                    '<span class="mm-face mm-back" aria-hidden="true">' + BFJ.omoji('🕊️') + '</span>' +
                    '<span class="mm-face mm-front">' + BFJ.bimg(c.img, c.emoji) +
                    '<small class="mm-name">' + BFJ.esc(BFJ.T(c.label)) + '</small></span></button>';
            }).join('') + '</div>' +
            '<div class="mm-found" id="mmFound" aria-live="polite"></div>';

        el.querySelectorAll('.mm-card').forEach(function (b) {
            b.addEventListener('click', function () { flip(parseInt(b.getAttribute('data-i'), 10), b); });
        });

        // Vistazo inicial: las cartas se muestran 1.4s — ayuda de memoria
        // para los más pequeños antes de empezar la ronda
        lock = true;
        el.querySelectorAll('.mm-card').forEach(function (b) { b.classList.add('open'); });
        setTimeout(function () {
            el.querySelectorAll('.mm-card.open').forEach(function (b) {
                b.classList.remove('open');
            });
            lock = false;
        }, 1400);

        function flip(i, btn) {
            if (lock || btn.classList.contains('open') || btn.classList.contains('done')) { return; }
            BFJ.snd('click');
            btn.classList.add('open');
            btn.setAttribute('aria-label', BFJ.T('Carta') + ' ' + (i + 1) + ': ' + cards[i].label);
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
                    BFJ.snd('pop');
                    BFJ.burst(a.btn); BFJ.burst(b.btn); // US-234 — la pareja estalla en ⭐
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
                    a.btn.setAttribute('aria-label', BFJ.T('Carta') + ' ' + (a.i + 1) + ' ' + BFJ.T('de') + ' ' + cards.length);
                    b.btn.setAttribute('aria-label', BFJ.T('Carta') + ' ' + (b.i + 1) + ' ' + BFJ.T('de') + ' ' + cards.length);
                    open = []; lock = false;
                }, 750);
            }
        }

        function end() {
            var stars = moves <= 10 ? 5 : (moves <= 14 ? 4 : (moves <= 18 ? 3 : (moves <= 24 ? 2 : 1)));
            BFJ.celebrate({
                slug: 'memory', stars: stars, perfect: moves <= 10,
                emoji: moves <= 10 ? '🏆' : '🎉',
                title: moves <= 10 ? BFJ.T('¡Memoria de campeón!') : BFJ.T('¡Completaste el memory!'),
                extra: BFJ.T('Encontraste') + ' ' + pairs.length + ' ' + BFJ.T('parejas en') + ' ' + moves + ' ' + BFJ.T('movimientos'),
                onAgain: round // reinicio sin recargar la página
            });
        }
        }
    }).catch(function () {
        el.innerHTML = '<section class="card notice"><p>' + BFJ.T('No pude cargar el juego 😢 Intenta de nuevo.') + '</p></section>';
    });
});
