// Biblia Fácil — motor de juegos bíblicos (EPIC 17 / US-170+171).
// Global BFJ: estrellas persistentes, confetti, sonidos Web Audio,
// shake/pop, timer animado, overlay de celebración, registro de juegos.
(function () {
    'use strict';

    // Base de assets a partir del propio <script src=".../juegos.js">
    var scripts = document.getElementsByTagName('script');
    var BASE = '';
    for (var i = scripts.length - 1; i >= 0; i--) {
        if (/juegos\.js/.test(scripts[i].src)) {
            BASE = scripts[i].src.replace(/juegos\.js.*$/, '');
            break;
        }
    }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function shuffle(a) {
        for (var i = a.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var t = a[i]; a[i] = a[j]; a[j] = t;
        }
        return a;
    }
    function pick(a, n) { return shuffle(a.slice()).slice(0, n); }

    // ============================ Estrellas / progreso ========================
    var data = { stars: {}, plays: {}, stickers: [] };
    try {
        var raw = JSON.parse(localStorage.getItem('bf_games') || 'null');
        if (raw && typeof raw === 'object') {
            data.stars = raw.stars || {};
            data.plays = raw.plays || {};
            data.stickers = raw.stickers || [];
        }
    } catch (e) {}
    function save() {
        try { localStorage.setItem('bf_games', JSON.stringify(data)); } catch (e) {}
    }
    var LEVELS = [
        [0, '🌱 Explorador'], [15, '📗 Aprendiz'], [50, '🏅 Maestro'], [120, '👑 Leyenda']
    ];

    // ============================ Stickers (US-179) ===========================
    // check(d, ctx) → bool; ctx = {perfect, slug}
    var STICKERS = [
        { id: 's10',  emoji: '🥉', name: 'Primeras 10 estrellas',   check: function (d) { return starTotal(d) >= 10; } },
        { id: 's30',  emoji: '🥈', name: '30 estrellas',            check: function (d) { return starTotal(d) >= 30; } },
        { id: 's75',  emoji: '🥇', name: '75 estrellas',            check: function (d) { return starTotal(d) >= 75; } },
        { id: 's200', emoji: '💎', name: '200 estrellas',           check: function (d) { return starTotal(d) >= 200; } },
        { id: 'multi', emoji: '🧭', name: 'Jugó 4 juegos distintos', check: function (d) {
                var n = 0; for (var k in d.stars) { if (d.stars[k] > 0) { n++; } } return n >= 4;
            } },
        { id: 'all7', emoji: '🎮', name: 'Probó los 7 juegos',      check: function (d) {
                var n = 0; for (var k in d.plays) { if (d.plays[k] > 0) { n++; } } return n >= 7;
            } },
        { id: 'perfect', emoji: '🏆', name: 'Ronda perfecta',       check: function (d, ctx) { return !!(ctx && ctx.perfect); } },
        { id: 'collector', emoji: '🌟', name: 'Estrellas en los 7 juegos', check: function (d) {
                var n = 0; for (var k in d.stars) { if (d.stars[k] > 0) { n++; } } return n >= 7;
            } }
    ];
    function starTotal(d) {
        var t = 0;
        for (var k in d.stars) { t += d.stars[k] || 0; }
        return t;
    }
    // Nivel actual + progreso hacia el siguiente (US-231)
    function levelInfo() {
        var t = starTotal(data), i = 0;
        while (i + 1 < LEVELS.length && t >= LEVELS[i + 1][0]) { i++; }
        var next = LEVELS[i + 1] || null;
        return { name: LEVELS[i][1], t: t, base: LEVELS[i][0], next: next ? next[0] : null };
    }

    // Desafío del día (US-233) — bf_daily = "Ymd:slug" del reto ya completado.
    // `daily` se activa solo si ?desafio= coincide con la fecha del servidor
    // (data-daily del shell), así no se puede inventar una fecha para el bonus.
    var daily = null;
    function dailyGet() { try { return localStorage.getItem('bf_daily') || ''; } catch (e) { return ''; } }
    function dailyDone(date, slug) { return dailyGet() === date + ':' + slug; }
    function dailyMarkDone(date, slug) { try { localStorage.setItem('bf_daily', date + ':' + slug); } catch (e) {} }
    function checkStickers(ctx) {
        var news = [];
        STICKERS.forEach(function (s) {
            if (data.stickers.indexOf(s.id) < 0 && s.check(data, ctx)) {
                data.stickers.push(s.id);
                news.push(s);
            }
        });
        if (news.length) { save(); }
        return news;
    }

    // Movimiento reducido (UX-04): sin confetti ni animaciones JS intensas
    function reducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    // ============================ Sonidos (Web Audio) =========================
    var actx = null;
    var muted = false;
    try { muted = localStorage.getItem('bf_sound') === '0'; } catch (e) {}
    function ctx() {
        if (!actx) {
            var AC = window.AudioContext || window.webkitAudioContext;
            if (!AC) { return null; }
            actx = new AC();
        }
        if (actx.state === 'suspended') { actx.resume(); }
        return actx;
    }
    function tone(freq, t0, dur, type, vol) {
        var c = ctx();
        if (!c) { return; }
        var o = c.createOscillator(), g = c.createGain();
        o.type = type || 'sine';
        o.frequency.value = freq;
        g.gain.setValueAtTime(vol || .18, c.currentTime + t0);
        g.gain.exponentialRampToValueAtTime(.001, c.currentTime + t0 + dur);
        o.connect(g); g.connect(c.destination);
        o.start(c.currentTime + t0);
        o.stop(c.currentTime + t0 + dur + .02);
    }
    function snd(kind) {
        buzz(kind); // US-231 — háptica táctil gratis en móviles (no hace ruido)
        if (muted) { return; }
        try {
            if (kind === 'click') { tone(660, 0, .07, 'triangle'); }
            else if (kind === 'ok') { tone(523, 0, .12, 'triangle'); tone(784, .09, .18, 'triangle'); }
            else if (kind === 'bad') { tone(233, 0, .16, 'sawtooth', .1); tone(175, .1, .22, 'sawtooth', .1); }
            else if (kind === 'win') {
                [523, 659, 784, 1047].forEach(function (f, i) { tone(f, i * .13, .25, 'triangle'); });
            } else if (kind === 'tick') { tone(880, 0, .04, 'square', .06); }
        } catch (e) {}
    }
    // Vibración corta por resultado — navigator.vibrate no existe en iOS, no pasa nada
    function buzz(kind) {
        if (!navigator.vibrate) { return; }
        try {
            if (kind === 'ok') { navigator.vibrate(40); }
            else if (kind === 'bad') { navigator.vibrate([60, 40, 60]); }
            else if (kind === 'win') { navigator.vibrate([40, 50, 40, 50, 140]); }
        } catch (e) {}
    }

    // ============================ Confetti (canvas) ===========================
    function confetti(dur) {
        if (reducedMotion()) { return; }
        var cv = document.createElement('canvas');
        cv.className = 'bfj-confetti';
        cv.width = innerWidth; cv.height = innerHeight;
        document.body.appendChild(cv);
        var x = cv.getContext('2d');
        var COLORS = ['#2e4a8a', '#b8912f', '#e8590c', '#37b24d', '#f06595', '#4dabf7'];
        var parts = [];
        for (var i = 0; i < 130; i++) {
            parts.push({
                x: innerWidth / 2 + (Math.random() - .5) * innerWidth * .35,
                y: innerHeight * .3,
                vx: (Math.random() - .5) * 14,
                vy: -Math.random() * 13 - 4,
                s: Math.random() * 8 + 4,
                c: COLORS[i % COLORS.length],
                r: Math.random() * 6.28,
                vr: (Math.random() - .5) * .3
            });
        }
        var t0 = performance.now(), life = dur || 1600;
        (function frame(t) {
            var dt = t - t0;
            x.clearRect(0, 0, cv.width, cv.height);
            parts.forEach(function (p) {
                p.x += p.vx; p.y += p.vy; p.vy += .32; p.r += p.vr;
                x.save();
                x.translate(p.x, p.y); x.rotate(p.r);
                x.globalAlpha = Math.max(0, 1 - dt / life);
                x.fillStyle = p.c;
                x.fillRect(-p.s / 2, -p.s / 2, p.s, p.s * .6);
                x.restore();
            });
            if (dt < life) { requestAnimationFrame(frame); }
            else { cv.remove(); }
        })(t0);
    }

    // ============================ FX helpers ==================================
    function shake(el) {
        el.classList.remove('bfj-shake');
        void el.offsetWidth;
        el.classList.add('bfj-shake');
    }
    function pop(el) {
        el.classList.remove('bfj-pop');
        void el.offsetWidth;
        el.classList.add('bfj-pop');
    }

    // ============================ Timer animado ===============================
    // Uso: var t = BFJ.timer(barEl, 10, onEnd) — retorna {stop(), left()}
    function timer(el, secs, onEnd) {
        var t0 = performance.now(), dead = t0 + secs * 1000, stopped = false, lastSec = secs;
        function frame(t) {
            if (stopped) { return; }
            var left = Math.max(0, dead - t) / 1000;
            el.style.setProperty('--tl', (left / secs));
            el.classList.toggle('low', left < 3.5);
            var s = Math.ceil(left);
            if (s !== lastSec) { lastSec = s; if (s <= 3) { snd('tick'); } }
            if (left <= 0) { stopped = true; onEnd && onEnd(); return; }
            requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
        return {
            stop: function () { stopped = true; },
            left: function () { return Math.max(0, dead - performance.now()) / 1000; }
        };
    }

    // ============================ Celebración =================================
    // Estrellas que vuelan del overlay al contador del header (US-231)
    function flyStars(ov, count) {
        var chipEl = document.getElementById('gameStars') || document.getElementById('totalStars');
        var chip = chipEl ? (chipEl.closest('.jh-stars') || chipEl.parentElement) : null;
        var from = ov ? ov.querySelector('.bfj-ovstars') : null;
        if (!chip || !from || !Element.prototype.animate) { return; }
        var r1 = from.getBoundingClientRect(), r2 = chip.getBoundingClientRect();
        var n = Math.min(count, 5);
        for (var i = 0; i < n; i++) {
            var s = document.createElement('span');
            s.textContent = '⭐'; s.className = 'bfj-fly';
            s.style.left = (r1.left + r1.width / 2 - 10) + 'px';
            s.style.top = (r1.top + r1.height / 2 - 10) + 'px';
            document.body.appendChild(s);
            var anim = s.animate([
                { transform: 'translate(0,0) scale(1.35)', opacity: 1 },
                { transform: 'translate(' + (r2.left + r2.width / 2 - r1.left - r1.width / 2) + 'px,'
                    + (r2.top + r2.height / 2 - r1.top - r1.height / 2) + 'px) scale(.45)', opacity: .9 }
            ], { duration: 620, delay: 110 * i, easing: 'cubic-bezier(.3,.7,.4,1)', fill: 'forwards' });
            (function (el, isLast) {
                anim.onfinish = function () {
                    el.remove();
                    if (isLast && chip) {
                        chip.classList.remove('bfj-bump'); void chip.offsetWidth;
                        chip.classList.add('bfj-bump');
                    }
                };
            })(s, i === n - 1);
        }
    }

    // US-234 — mini-explosión de estrellas desde un elemento (pareja, acierto)
    function burst(el) {
        if (reducedMotion() || !el || !Element.prototype.animate) { return; }
        var r = el.getBoundingClientRect();
        var cx = r.left + r.width / 2, cy = r.top + r.height / 2;
        for (var i = 0; i < 5; i++) {
            var s = document.createElement('span');
            s.textContent = '⭐'; s.className = 'bfj-fly';
            s.style.left = (cx - 8) + 'px'; s.style.top = (cy - 8) + 'px';
            s.style.fontSize = '.95rem';
            document.body.appendChild(s);
            var ang = (Math.PI * 2 * i) / 5 + Math.random() * .5;
            var dist = 46 + Math.random() * 32;
            var dx = Math.cos(ang) * dist, dy = Math.sin(ang) * dist;
            var anim = s.animate([
                { transform: 'translate(0,0) scale(.4)', opacity: 1 },
                { transform: 'translate(' + dx + 'px,' + (dy - 26) + 'px) scale(1.05)', opacity: 1, offset: .6 },
                { transform: 'translate(' + (dx * 1.25) + 'px,' + (dy - 14) + 'px) scale(.3)', opacity: 0 }
            ], { duration: 560, easing: 'ease-out', fill: 'forwards' });
            anim.onfinish = (function (node) { return function () { node.remove(); }; })(s);
        }
    }

    // BFJ.celebrate({slug, stars, emoji, title, extra, perfect, onAgain})
    function celebrate(o) {
        // US-233 — bonus ×2 una sola vez por fecha+slug del desafío
        var dBonus = false;
        if (daily && daily.slug === o.slug && !dailyDone(daily.date, daily.slug)) {
            o = Object.assign({}, o, { stars: o.stars * 2 });
            dailyMarkDone(daily.date, o.slug);
            dBonus = true;
        }
        BFJ.stars.add(o.slug, o.stars);
        if (window.BF_TRACK) {
            window.BF_TRACK('game_win', o.slug);
            window.BF_TRACK('game_stars', o.slug, o.stars);
            if (o.perfect) { window.BF_TRACK('game_perfect', o.slug); }
            var secs = BFJ._t0 ? Math.round((Date.now() - BFJ._t0) / 1000) : 0;
            BFJ._t0 = Date.now(); // siguiente ronda mide desde aquí
            if (secs >= 3 && secs <= 1800) { window.BF_TRACK('game_s', o.slug, secs); }
        }
        var news = checkStickers({ slug: o.slug, perfect: o.perfect });
        var retFocus = document.activeElement;
        var ov = document.createElement('div');
        ov.className = 'bfj-ov';
        ov.setAttribute('role', 'dialog');
        ov.setAttribute('aria-modal', 'true');
        ov.setAttribute('aria-label', o.title || '¡Bien hecho!');
        ov.innerHTML =
            '<div class="bfj-ovcard bfj-pop">' +
            '<div class="bfj-ovemoji">' + (o.emoji || '🎉') + '</div>' +
            '<h2>' + esc(o.title || '¡Bien hecho!') + '</h2>' +
            '<div class="bfj-ovstars">' + (o.stars > 0
                ? '⭐'.repeat(Math.min(o.stars, 10)) : '☆') + '</div>' +
            '<p class="bfj-ovpts">+' + o.stars + ' estrella' + (o.stars === 1 ? '' : 's') + '</p>' +
            (dBonus ? '<div class="bfj-ovdaily">🗓 ¡Desafío del día! ⭐×2</div>' : '') +
            (o.extra ? '<p class="bfj-ovextra">' + esc(o.extra) + '</p>' : '') +
            (news.length ? '<div class="bfj-ovstick bfj-pop">🎁 ¡Sticker nuevo!<br>' +
                news.map(function (s) {
                    return '<span class="bfj-stick"><i class="bfj-gift" aria-hidden="true">🎁</i> ' + esc(s.name) +
                        '<i class="bfj-stemo" hidden>' + s.emoji + '</i></span>';
                }).join('') +
                '</div>' : '') +
            '<div class="bfj-ovbtns">' +
            (o.onAgain ? '<button type="button" class="jbtn jbtn-main" data-c="again">🔄 Otra vez</button>' : '') +
            '<button type="button" class="jbtn jbtn-ghost" data-c="hub">🎮 Juegos</button>' +
            '</div></div>';
        document.body.appendChild(ov);
        // US-231 — contador de estrellas en vivo + estrellas voladoras + unboxing
        var gsEl = document.getElementById('gameStars');
        if (gsEl) { gsEl.textContent = BFJ.stars.of(o.slug); }
        if (!reducedMotion()) {
            if (o.stars > 0) { setTimeout(function () { flyStars(ov, o.stars); }, 500); }
            ov.querySelectorAll('.bfj-stick').forEach(function (el, i) {
                var gift = el.querySelector('.bfj-gift'), emo = el.querySelector('.bfj-stemo');
                if (!gift || !emo) { return; }
                setTimeout(function () {
                    gift.textContent = emo.textContent;
                    gift.classList.remove('bfj-gift'); gift.classList.add('st-pop');
                }, 850 + i * 420);
            });
        } else {
            ov.querySelectorAll('.bfj-stick').forEach(function (el) {
                var gift = el.querySelector('.bfj-gift'), emo = el.querySelector('.bfj-stemo');
                if (gift && emo) { gift.textContent = emo.textContent; }
            });
        }
        var firstBtn = ov.querySelector('[data-c]');
        if (firstBtn) { firstBtn.focus(); }
        function dismiss(goHub) {
            ov.remove();
            if (retFocus && retFocus.focus) { retFocus.focus(); }
            if (goHub) {
                var hub = document.querySelector('.jg-head a');
                window.location.href = hub ? hub.href : '/juegos';
            }
        }
        snd('win');
        confetti();
        ov.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') { ev.stopPropagation(); dismiss(false); }
        });
        ov.addEventListener('click', function (ev) {
            var c = ev.target.closest('[data-c]');
            if (!c) { return; }
            if (c.getAttribute('data-c') === 'again' && o.onAgain) {
                dismiss(false); o.onAgain();
            } else {
                dismiss(true);
            }
        });
    }

    // ============================ BFJ público =================================
    var BFJ = {
        BASE: BASE,
        esc: esc,
        shuffle: shuffle,
        pick: pick,
        snd: snd,
        muted: function (m) {
            if (m === undefined) { return muted; }
            muted = !!m;
            try { localStorage.setItem('bf_sound', muted ? '0' : '1'); } catch (e) {}
            return muted;
        },
        confetti: confetti,
        burst: burst,
        shake: shake,
        pop: pop,
        timer: timer,
        celebrate: celebrate,
        stars: {
            total: function () {
                var t = 0;
                for (var k in data.stars) { t += data.stars[k] || 0; }
                return t;
            },
            of: function (slug) { return data.stars[slug] || 0; },
            add: function (slug, n) {
                data.stars[slug] = (data.stars[slug] || 0) + (n | 0);
                save();
            }
        },
        level: function () {
            var t = this.stars.total(), lv = LEVELS[0][1];
            for (var i = 0; i < LEVELS.length; i++) {
                if (t >= LEVELS[i][0]) { lv = LEVELS[i][1]; }
            }
            return lv;
        },
        played: function (slug) {
            data.plays[slug] = (data.plays[slug] || 0) + 1;
            save();
        },
        stickers: {
            all: STICKERS,
            mine: function () { return data.stickers; },
            check: checkStickers
        },
        games: {},
        define: function (slug, init) { this.games[slug] = init; },
        fetchBank: function (file) {
            return fetch(BASE + 'games/' + file).then(function (r) {
                if (!r.ok) { throw new Error('bank ' + r.status); }
                return r.json();
            });
        }
    };
    window.BFJ = BFJ;

    // ============================ Arranque ====================================
    function soundToggle(host) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'bfj-sound';
        function paint() {
            b.textContent = muted ? '🔇' : '🔊';
            b.setAttribute('aria-label', muted ? 'Activar sonido' : 'Silenciar sonidos');
            b.setAttribute('aria-pressed', muted ? 'true' : 'false');
        }
        b.addEventListener('click', function () { BFJ.muted(!muted); paint(); });
        paint();
        host.appendChild(b);
    }

    function start() {
        // Hub: pintar progreso
        var ts = document.getElementById('totalStars');
        if (ts) {
            ts.textContent = BFJ.stars.total();
            var lb = document.getElementById('levelBadge');
            var li = levelInfo();
            if (lb) {
                lb.textContent = li.name;
                lb.title = li.next
                    ? li.t + '/' + li.next + ' ⭐ para el siguiente nivel'
                    : li.t + ' ⭐ — ¡nivel máximo!';
            }
            var barFill = document.querySelector('.jh-levelbar span');
            if (barFill) {
                var pct = li.next
                    ? Math.round((li.t - li.base) / (li.next - li.base) * 100)
                    : 100;
                barFill.style.width = pct + '%';
                barFill.parentElement.setAttribute('aria-valuenow', String(pct));
            }
            var score = document.querySelector('.jh-score');
            if (score) { soundToggle(score); }
            // US-233 — estado del desafío del día en el hub
            var dc = document.getElementById('dailyCard');
            if (dc && dailyDone(dc.getAttribute('data-date'), dc.getAttribute('data-slug'))) {
                dc.classList.add('done');
                var ds = document.getElementById('dailyState');
                if (ds) { ds.textContent = '✅ ¡Hecho!'; }
                var dh = document.getElementById('dailyHint');
                if (dh) { dh.textContent = 'Vuelve mañana por otro desafío'; }
            }
            // US-232 — estados del camino: done / now (siguiente) / todo / master
            var nowMarked = false;
            document.querySelectorAll('.jh-node[data-slug]').forEach(function (node) {
                var slug = node.getAttribute('data-slug');
                var n = BFJ.stars.of(slug);
                var b = node.querySelector('[data-best]');
                if (n > 0) {
                    if (b) { b.textContent = '⭐ ' + n; b.classList.add('won'); }
                    node.classList.add(n >= 15 ? 'is-master' : 'is-done');
                } else {
                    if (!(data.plays[slug] || 0) && b) { b.textContent = '✨ ¡Nuevo!'; }
                    if (!nowMarked) { node.classList.add('is-now'); nowMarked = true; }
                    else { node.classList.add('is-todo'); }
                }
            });
            // Álbum de stickers: por hitos ya ganados (evalúa sobre historial)
            checkStickers({});
            var wall = document.getElementById('stickerWall');
            if (wall) {
                var mine = BFJ.stickers.mine();
                wall.innerHTML = STICKERS.map(function (s) {
                    var got = mine.indexOf(s.id) >= 0;
                    return '<div class="st' + (got ? ' got' : '') + '" title="' + esc(s.name) + '">' +
                        '<span>' + (got ? s.emoji : '❓') + '</span>' +
                        '<small>' + (got ? esc(s.name) : '???') + '</small></div>';
                }).join('');
            }
        }
        // Shell de juego: estrellas del juego + montar
        var app = document.getElementById('gameApp');
        if (app) {
            var slug = app.getAttribute('data-game');
            var gs = document.getElementById('gameStars');
            if (gs) {
                gs.textContent = BFJ.stars.of(slug);
                soundToggle(gs.parentElement);
            }
            BFJ.played(slug);
            // US-233 — activar desafío si la URL trae la fecha correcta del servidor
            var dm = location.search.match(/[?&]desafio=(\d{8})/);
            if (dm && dm[1] === app.getAttribute('data-daily')) {
                daily = { date: dm[1], slug: slug };
                var head = document.querySelector('.jg-head');
                if (head) {
                    var tag = document.createElement('span');
                    tag.className = 'jg-daily-tag';
                    tag.textContent = dailyDone(daily.date, slug)
                        ? '🗓 Desafío completado hoy'
                        : '🗓 Desafío del día · ⭐×2';
                    head.appendChild(tag);
                }
            }
            if (BFJ.games[slug]) {
                BFJ._t0 = Date.now(); // para métrica game_s (tiempo por ronda)
                BFJ.games[slug](app);
            } else {
                app.innerHTML = '<section class="card notice"><p>Este juego está en camino 🔧</p></section>';
            }
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
