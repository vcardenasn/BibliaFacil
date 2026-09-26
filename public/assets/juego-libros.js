// US-176 — Ordena los Libros: bloques del canon, tap en orden sobre tarjetas.
BFJ.define('libros', function (el) {
    el.innerHTML = '<section class="card notice"><p>Cargando… ⏳</p></section>';
    BFJ.fetchBank('libros.json').then(function (blocks) {
        picker();

        function picker() {
            el.innerHTML =
                '<div class="tr-cats">' +
                blocks.map(function (b) {
                    return '<button type="button" class="tr-cat" data-b="' + b.id + '">' +
                        '<span>' + b.emoji + '</span><strong>' + BFJ.esc(b.name) + '</strong>' +
                        '<small class="lb-diff">' + b.books.length + ' libros · ' + b.diff + '</small></button>';
                }).join('') +
                '</div><p class="jh-note">Aprende el orden de la Biblia bloque por bloque 📚</p>';
            el.querySelectorAll('[data-b]').forEach(function (b) {
                b.addEventListener('click', function () {
                    BFJ.snd('click');
                    play(blocks.filter(function (x) { return x.id === b.getAttribute('data-b'); })[0]);
                });
            });
        }

        function play(block) {
            var n = block.books.length;
            var order = BFJ.shuffle(block.books.map(function (_, i) { return i; }));
            var next = 0, errors = 0, done = 0;

            el.innerHTML =
                '<div class="hs-slots">' +
                block.books.map(function (_, i) {
                    return '<div class="hs-slot lb-slot" data-i="' + i + '"><em>' + (i + 1) + '</em><div class="hs-into"></div></div>';
                }).join('') +
                '</div>' +
                '<p class="hs-hint">📚 ' + BFJ.esc(block.name) + ' — toca los libros en orden</p>' +
                '<div class="hs-pool lb-pool">' +
                order.map(function (bi) {
                    return '<button type="button" class="hs-card lb-card" data-bi="' + bi + '">' +
                        BFJ.esc(block.books[bi]) + '</button>';
                }).join('') +
                '</div>';

            el.querySelectorAll('.lb-card').forEach(function (card) {
                card.addEventListener('click', function () {
                    var bi = parseInt(card.getAttribute('data-bi'), 10);
                    if (bi === next) {
                        BFJ.snd('ok');
                        var slot = el.querySelector('.hs-slot[data-i="' + bi + '"]');
                        slot.querySelector('.hs-into').innerHTML =
                            '<div class="hs-placed bfj-pop"><small class="lb-name">' +
                            BFJ.esc(block.books[bi]) + '</small></div>';
                        slot.classList.add('filled');
                        BFJ.burst(slot); // US-234
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
                var stars = Math.max(1, 5 - Math.ceil(errors / 2));
                BFJ.celebrate({
                    slug: 'libros', stars: stars, perfect: errors === 0,
                    emoji: errors === 0 ? '🏆' : '📚',
                    title: errors === 0 ? '¡Orden perfecto!' : '¡Bloque completado!',
                    extra: block.name + ' · ' + n + ' libros · ' +
                        (errors === 0 ? 'sin errores' : errors + ' error' + (errors === 1 ? '' : 'es')),
                    onAgain: picker
                });
            }
        }
    }).catch(function () {
        el.innerHTML = '<section class="card notice"><p>No pude cargar el juego 😢 Intenta de nuevo.</p></section>';
    });
});
