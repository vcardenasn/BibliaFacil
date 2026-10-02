// US-173 — Trivia Bíblica: categorías, 10 preguntas, timer 15s, racha.
BFJ.define('trivia', function (el) {
    var CATS = {
        mezcla: { name: BFJ.T('¡Mezcla!'), emoji: '🎲' },
        personajes: { name: BFJ.T('Personajes'), emoji: '🧔' },
        historias: { name: BFJ.T('Historias'), emoji: '📖' },
        milagros: { name: BFJ.T('Milagros de Jesús'), emoji: '✨' },
        animales: { name: BFJ.T('Animales'), emoji: '🦁' },
        genesis: { name: BFJ.T('Génesis'), emoji: '🌍' },
        exodo: { name: BFJ.T('El Éxodo'), emoji: '🌊' },
        jesus: { name: BFJ.T('Vida de Jesús'), emoji: '🕊️' },
        parabolas: { name: BFJ.T('Parábolas'), emoji: '🌱' },
        profetas: { name: BFJ.T('Profetas'), emoji: '📣' },
        reyes: { name: BFJ.T('Reyes'), emoji: '👑' },
        mujeres: { name: BFJ.T('Mujeres'), emoji: '👩' },
        apostoles: { name: BFJ.T('Apóstoles'), emoji: '✝️' },
        salmos: { name: BFJ.T('Salmos'), emoji: '🎵' },
        lugares: { name: BFJ.T('Lugares'), emoji: '🏖️' },
        navidad: { name: BFJ.T('Navidad'), emoji: '🌟' },
        pascua: { name: BFJ.T('La Resurrección'), emoji: '🌅' },
        oracion: { name: BFJ.T('La Oración'), emoji: '🙏' },
        numeros: { name: BFJ.T('Números'), emoji: '🔢' },
        alimentos: { name: BFJ.T('Alimentos'), emoji: '🍞' },
        libros: { name: BFJ.T('Los Libros'), emoji: '📚' },
        pablo: { name: BFJ.T('Hechos y Pablo'), emoji: '🚢' },
        milagrosat: { name: BFJ.T('Milagros del AT'), emoji: '🔥' },
        versiculos: { name: BFJ.T('Versículos famosos'), emoji: '📜' }
    };
    el.innerHTML = '<section class="card notice"><p>' + BFJ.T('Cargando… ⏳') + '</p></section>';
    BFJ.fetchBank('trivia.json').then(function (bank) {
        catScreen();

        function catScreen() {
            var html = '<div class="tr-cats">';
            for (var k in CATS) {
                html += '<button type="button" class="tr-cat" data-cat="' + k + '">' +
                    '<span class="hs-cover">' + BFJ.omoji(CATS[k].emoji) + '</span><strong>' + CATS[k].name + '</strong></button>';
            }
            el.innerHTML = html + '</div><p class="jh-note">' + BFJ.T('Elige una categoría — ¡10 preguntas contra el reloj!') + '</p>';
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
                    var rapid = t && t.left() > 11; // <4s tras aparecer = ⚡ rápida
                    if (rapid) {
                        fast++;
                        var qbox = el.querySelector('.vf-qbox');
                        if (qbox) {
                            qbox.insertAdjacentHTML('beforeend',
                                '<span class="vf-fast bfj-pop">' + BFJ.T('⚡ ¡rápida!') + '</span>');
                        }
                    }
                    BFJ.snd(rapid ? 'sparkle' : 'ok');
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
                var title = ok >= 9 ? BFJ.T('¡Experto bíblico!') : (ok >= 6 ? BFJ.T('¡Muy bien!') : BFJ.T('¡Sigue practicando!'));
                BFJ.celebrate({
                    slug: 'trivia', stars: ok + bonus + fastBonus, emoji: emoji, title: title,
                    perfect: ok === qs.length,
                    extra: ok + ' ' + BFJ.T('de') + ' ' + qs.length + ' ' + BFJ.T('correctas') + ' · ' + CATS[cat].name +
                        (bestStreak >= 3 ? ' · ' + BFJ.T('racha máx') + ' 🔥x' + bestStreak : '') +
                        (fast ? ' · ⚡x' + fast + ' ' + BFJ.T('rápidas') : '') +
                        (bonus + fastBonus ? ' · +' + (bonus + fastBonus) + '⭐ ' + BFJ.T('bonus') : ''),
                    againLabel: BFJ.T('🔁 Otra categoría'),
                    onAgain: catScreen
                });
            }

            render();
        }
    }).catch(function () {
        el.innerHTML = '<section class="card notice"><p>' + BFJ.T('No pude cargar el juego 😢 Intenta de nuevo.') + '</p></section>';
    });
});
