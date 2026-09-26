// US-174 — Ordena la Historia: escenas mezcladas, tócalas en el orden correcto.
BFJ.define('historia', function (el) {
    el.innerHTML = '<section class="card notice"><p>Cargando… ⏳</p></section>';
    BFJ.fetchBank('historia.json').then(function (bank) {
        picker();

        function picker() {
            el.innerHTML =
                '<div class="tr-cats">' +
                bank.map(function (s) {
                    return '<button type="button" class="tr-cat" data-s="' + s.id + '">' +
                        '<span>' + s.emoji + '</span><strong>' + BFJ.esc(s.name) + '</strong></button>';
                }).join('') +
                '</div><p class="jh-note">Elige una historia y pon sus escenas en orden 🎬</p>';
            el.querySelectorAll('[data-s]').forEach(function (b) {
                b.addEventListener('click', function () {
                    BFJ.snd('click');
                    play(bank.filter(function (s) { return s.id === b.getAttribute('data-s'); })[0]);
                });
            });
        }

        function play(story) {
            var n = story.scenes.length;
            var order = BFJ.shuffle(story.scenes.map(function (s, i) { return i; }));
            var next = 0, errors = 0, done = 0;

            el.innerHTML =
                '<div class="hs-slots">' +
                story.scenes.map(function (s, i) {
                    return '<div class="hs-slot" data-i="' + i + '"><em>' + (i + 1) + '</em><div class="hs-into"></div></div>';
                }).join('') +
                '</div>' +
                '<p class="hs-hint">👆 Toca las tarjetas en el orden correcto</p>' +
                '<div class="hs-pool">' +
                order.map(function (si) {
                    var s = story.scenes[si];
                    return '<button type="button" class="hs-card" data-si="' + si + '">' +
                        '<span>' + s.e + '</span><small>' + BFJ.esc(s.t) + '</small></button>';
                }).join('') +
                '</div>';

            el.querySelectorAll('.hs-card').forEach(function (card) {
                card.addEventListener('click', function () {
                    var si = parseInt(card.getAttribute('data-si'), 10);
                    if (si === next) {
                        // Correcto: vuela al slot
                        BFJ.snd('ok');
                        var slot = el.querySelector('.hs-slot[data-i="' + si + '"] .hs-into');
                        var s = story.scenes[si];
                        slot.innerHTML = '<div class="hs-placed bfj-pop"><span>' + s.e + '</span><small>' + BFJ.esc(s.t) + '</small></div>';
                        el.querySelector('.hs-slot[data-i="' + si + '"]').classList.add('filled');
                        card.remove();
                        next++;
                        done++;
                        if (done === n) { setTimeout(end, 550); }
                    } else {
                        errors++;
                        BFJ.snd('bad');
                        BFJ.shake(card);
                    }
                });
            });

            function end() {
                var stars = Math.max(1, 5 - errors);
                BFJ.celebrate({
                    slug: 'historia', stars: stars, perfect: errors === 0,
                    emoji: errors === 0 ? '🏆' : '🎬',
                    title: errors === 0 ? '¡Orden perfecto!' : '¡Historia completada!',
                    extra: story.name + ' · ' + (errors === 0 ? 'sin errores' : errors + ' error' + (errors === 1 ? '' : 'es')),
                    onAgain: picker
                });
            }
        }
    }).catch(function () {
        el.innerHTML = '<section class="card notice"><p>No pude cargar el juego 😢 Intenta de nuevo.</p></section>';
    });
});
