// US-173 — Trivia Bíblica: categorías, 10 preguntas, timer 15s, racha.
BFJ.define('trivia', function (el) {
    var CATS = {
        mezcla: { name: '¡Mezcla!', emoji: '🎲' },
        personajes: { name: 'Personajes', emoji: '🧔' },
        historias: { name: 'Historias', emoji: '📖' },
        milagros: { name: 'Milagros de Jesús', emoji: '✨' },
        animales: { name: 'Animales', emoji: '🦁' },
        genesis: { name: 'Génesis', emoji: '🌍' },
        exodo: { name: 'El Éxodo', emoji: '🌊' },
        jesus: { name: 'Vida de Jesús', emoji: '🕊️' },
        parabolas: { name: 'Parábolas', emoji: '🌱' },
        profetas: { name: 'Profetas', emoji: '📣' },
        reyes: { name: 'Reyes', emoji: '👑' },
        mujeres: { name: 'Mujeres', emoji: '👩' },
        apostoles: { name: 'Apóstoles', emoji: '✝️' },
        salmos: { name: 'Salmos', emoji: '🎵' },
        lugares: { name: 'Lugares', emoji: '🏖️' },
        navidad: { name: 'Navidad', emoji: '🌟' },
        pascua: { name: 'La Resurrección', emoji: '🌅' },
        oracion: { name: 'La Oración', emoji: '🙏' },
        numeros: { name: 'Números', emoji: '🔢' },
        alimentos: { name: 'Alimentos', emoji: '🍞' },
        libros: { name: 'Los Libros', emoji: '📚' },
        pablo: { name: 'Hechos y Pablo', emoji: '🚢' },
        milagrosat: { name: 'Milagros del AT', emoji: '🔥' },
        versiculos: { name: 'Versículos famosos', emoji: '📜' }
    };
    el.innerHTML = '<section class="card notice"><p>Cargando… ⏳</p></section>';
    BFJ.fetchBank('trivia.json').then(function (bank) {
        catScreen();

        function catScreen() {
            var html = '<div class="tr-cats">';
            for (var k in CATS) {
                html += '<button type="button" class="tr-cat" data-cat="' + k + '">' +
                    '<span class="hs-cover">' + BFJ.omoji(CATS[k].emoji) + '</span><strong>' + CATS[k].name + '</strong></button>';
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
            var i = 0, ok = 0, streak = 0, bestStreak = 0, fast = 0, t = null, locked = false;

            function render() {
                if (i >= qs.length) { return end(); }
                locked = false;
                var q = qs[i];
                var opts = q.o.map(function (txt, ix) {
                    // US-234 — las opciones entran volteando como cartas, escalonadas
                    return '<button type="button" class="jbtn tr-opt tr-in" data-i="' + ix + '"' +
                        ' style="--d:' + ix * 70 + 'ms">' + BFJ.esc(txt) + '</button>';
                }).join('');
                el.innerHTML =
                    '<div class="vf-qbox">' +
                    '<div class="vf-prog"><span>' + CATS[cat].emoji + ' ' + (i + 1) + ' / ' + qs.length + '</span>' +
                    '<span class="vf-streak' + (streak >= 3 ? ' hot' : '') + '">' +
                    (streak > 1 ? '🔥 x' + streak : '') + '</span></div>' +
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
                if (hit) {
                    ok++; streak++;
                    if (streak > bestStreak) { bestStreak = streak; }
                    if (t && t.left() > 11) { // <4s tras aparecer = ⚡ rápida
                        fast++;
                        var qbox = el.querySelector('.vf-qbox');
                        if (qbox) {
                            qbox.insertAdjacentHTML('beforeend',
                                '<span class="vf-fast bfj-pop">⚡ ¡rápida!</span>');
                        }
                    }
                    BFJ.snd('ok');
                    BFJ.burst(el.querySelector('.tr-opt.ok'));
                }
                else { streak = 0; BFJ.snd('bad'); BFJ.shake(el.firstElementChild); }
                i++;
                setTimeout(render, hit ? 750 : 1600);
            }

            function end() {
                var bonus = Math.floor(bestStreak / 5);  // racha de 5+ suma ⭐
                var fastBonus = Math.floor(fast / 3);    // 3 rápidas ⚡ = +1⭐
                var emoji = ok >= 9 ? '🏆' : (ok >= 6 ? '🎉' : '💪');
                var title = ok >= 9 ? '¡Experto bíblico!' : (ok >= 6 ? '¡Muy bien!' : '¡Sigue practicando!');
                BFJ.celebrate({
                    slug: 'trivia', stars: ok + bonus + fastBonus, emoji: emoji, title: title,
                    perfect: ok === qs.length,
                    extra: ok + ' de ' + qs.length + ' correctas · ' + CATS[cat].name +
                        (bestStreak >= 3 ? ' · racha máx 🔥x' + bestStreak : '') +
                        (fast ? ' · ⚡x' + fast + ' rápidas' : '') +
                        (bonus + fastBonus ? ' · +' + (bonus + fastBonus) + '⭐ bonus' : ''),
                    againLabel: '🔁 Otra categoría',
                    onAgain: catScreen
                });
            }

            render();
        }
    }).catch(function () {
        el.innerHTML = '<section class="card notice"><p>No pude cargar el juego 😢 Intenta de nuevo.</p></section>';
    });
});
