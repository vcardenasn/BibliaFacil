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

    // i18n: window.BF_T mapea 'Texto en español' → idioma activo.
    var TMAP = window.BF_T || {};
    function T(s) { return TMAP[s] || s; }

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
    var data = { stars: {}, plays: {}, stickers: [], vl: {}, vlb: {}, missions: {}, wk: null };
    try {
        var raw = JSON.parse(localStorage.getItem('bf_games') || 'null');
        if (raw && typeof raw === 'object') {
            data.stars = raw.stars || {};
            data.plays = raw.plays || {};
            data.stickers = raw.stickers || [];
            data.vl = raw.vl || {};
            data.vlb = raw.vlb || {};
            data.missions = raw.missions || {};
            data.wk = raw.wk && raw.wk.w ? raw.wk : null;
        }
    } catch (e) {}
    function save() {
        try { localStorage.setItem('bf_games', JSON.stringify(data)); } catch (e) {}
    }
    var LEVELS = [
        [0, '🌱 ' + T('Explorador')], [15, '📗 ' + T('Aprendiz')], [40, '🕯️ ' + T('Discípulo')],
        [80, '⭐ ' + T('Siervo Fiel')], [140, '⚔️ ' + T('Guerrero')], [220, '🔥 ' + T('Profeta')],
        [320, '✝️ ' + T('Apóstol')], [450, '👑 ' + T('Leyenda')]
    ];

    // ============================ Stickers (US-179) ===========================
    // check(d, ctx) → bool; ctx = {perfect, slug}
    var STICKERS = [
        { id: 's10',  emoji: '🥉', name: T('Primeras 10 estrellas'),   check: function (d) { return starTotal(d) >= 10; } },
        { id: 's30',  emoji: '🥈', name: T('30 estrellas'),            check: function (d) { return starTotal(d) >= 30; } },
        { id: 's75',  emoji: '🥇', name: T('75 estrellas'),            check: function (d) { return starTotal(d) >= 75; } },
        { id: 's200', emoji: '💎', name: T('200 estrellas'),           check: function (d) { return starTotal(d) >= 200; } },
        { id: 'multi', emoji: '🧭', name: T('Jugó 4 juegos distintos'), check: function (d) {
                var n = 0; for (var k in d.stars) { if (k[0] !== '_' && d.stars[k] > 0) { n++; } } return n >= 4;
            } },
        { id: 'all7', emoji: '🎮', name: T('Probó 7 juegos distintos'), check: function (d) {
                var n = 0; for (var k in d.plays) { if (d.plays[k] > 0) { n++; } } return n >= 7;
            } },
        { id: 'perfect', emoji: '🏆', name: T('Ronda perfecta'),       check: function (d, ctx) { return !!(ctx && ctx.perfect); } },
        { id: 'collector', emoji: '🌟', name: T('Estrellas en 7 juegos distintos'), check: function (d) {
                var n = 0; for (var k in d.stars) { if (k[0] !== '_' && d.stars[k] > 0) { n++; } } return n >= 7;
            } },
        // Progreso por niveles de "Completa el Versículo"
        { id: 'vj3', emoji: '🌱', name: T('Semillas de la Palabra'), check: function (d) {
                return ((d.vl || {}).versiculo || 0) >= 3;
            } },
        { id: 'vj5', emoji: '📖', name: T('Estudiante de la Palabra'), check: function (d) {
                return ((d.vl || {}).versiculo || 0) >= 5;
            } },
        { id: 'vj7', emoji: '👑', name: T('Maestro del Versículo'), check: function (d) {
                return ((d.vl || {}).versiculo || 0) >= 7;
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

    // ==================== Misiones semanales ================================
    // 3 de este catálogo por semana ISO (semilla determinística — todos los
    // jugadores ven las mismas). wk = contadores solo de la semana actual.
    var MISION_REWARD = 4;
    var MISSIONS = [
        { id: 'm-games',   emoji: '🧭', name: T('Juega 3 juegos distintos'),     need: 3,
            pro: function (w) { var n = 0; for (var k in w.plays) { n++; } return n; } },
        { id: 'm-stars',   emoji: '⭐', name: T('Gana 15 estrellas'),            need: 15,
            pro: function (w) { return w.stars; } },
        { id: 'm-perfect', emoji: '🏆', name: T('Logra una ronda perfecta'),     need: 1,
            pro: function (w) { return w.perfects; } },
        { id: 'm-rounds',  emoji: '🎮', name: T('Juega 5 rondas'),               need: 5,
            pro: function (w) { var n = 0; for (var k in w.plays) { n += w.plays[k]; } return n; } },
        { id: 'm-levels',  emoji: '🗺️', name: T('Supera un nivel (Versículo o Paloma)'), need: 1,
            pro: function (w) { return w.levels; } },
        { id: 'm-stars30', emoji: '🌟', name: T('Gana 30 estrellas'),            need: 30,
            pro: function (w) { return w.stars; } }
    ];
    function weekKey() {
        var d = new Date();
        d = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        var dow = (d.getDay() + 6) % 7;             // lunes = 0
        d.setDate(d.getDate() - dow + 3);           // jueves ISO = semana/año ISO
        var jan4 = new Date(d.getFullYear(), 0, 4);
        var monday1 = new Date(d.getFullYear(), 0, 4 - ((jan4.getDay() + 6) % 7));
        return d.getFullYear() + '-W' + (1 + Math.round((d - monday1) / 6048e5));
    }
    function weekMissions() {
        var arr = MISSIONS.slice(), h = 5381, wk = weekKey();
        for (var i = 0; i < wk.length; i++) { h = (h * 33 + wk.charCodeAt(i)) >>> 0; }
        for (var j = arr.length - 1; j > 0; j--) {   // Fisher-Yates con LCG
            h = (h * 1664525 + 1013904223) >>> 0;
            var k = h % (j + 1), t = arr[j]; arr[j] = arr[k]; arr[k] = t;
        }
        return arr.slice(0, 3);
    }
    function wkGet() {
        var wk = weekKey();
        if (!data.wk || data.wk.w !== wk) {
            data.wk = { w: wk, plays: {}, stars: 0, perfects: 0, levels: 0 };
        }
        return data.wk;
    }
    // Revisa misiones activas y otorga la recompensa; devuelve las recién hechas
    function missionCheck() {
        var wk = weekKey(), news = [], w = wkGet();
        weekMissions().forEach(function (m) {
            if (data.missions[m.id] === wk) { return; }
            if (m.pro(w) >= m.need) {
                data.missions[m.id] = wk;
                news.push(m);
                data.stars._misiones = (data.stars._misiones || 0) + MISION_REWARD;
            }
        });
        if (news.length) { save(); }
        return news;
    }

    // Imágenes prediseñadas OpenMoji (CC BY-SA) — mapa emoji → archivo SVG a color.
    // Los juegos las usan como contenido visual (cartas, siluetas, portadas).
    var OMOJI = {
        '🧔':'1F9D4','🐳':'1F433','🚢':'1F6A2','🌈':'1F308','🦁':'1F981','🙏':'1F64F',
        '🪨':'1FAA8','👦':'1F466','🔥':'1F525','🌳':'1F333','👶':'1F476','⭐':'2B50',
        '🍞':'1F35E','🐟':'1F41F','🐑':'1F411','🧑‍🌾':'1F9D1-200D-1F33E','🌊':'1F30A',
        '🚶':'1F6B6','🏰':'1F3F0','🎺':'1F3BA','🍎':'1F34E','🐍':'1F40D','😴':'1F634',
        '🪜':'1FA9C','💇':'1F487','💪':'1F4AA','👑':'1F451','💃':'1F483','🧥':'1F9E5',
        '✉️':'2709','🌾':'1F33E','🌟':'1F31F','👩':'1F469','🏺':'1F3FA','👂':'1F442',
        '🐴':'1F434','🧱':'1F9F1','🌅':'1F305','🖐️':'1F590','🍯':'1F36F','🌑':'1F311',
        '📜':'1F4DC','📖':'1F4D6','🌍':'1F30D','💡':'1F4A1','👫':'1F46B','😌':'1F60C',
        '📢':'1F4E2','🔨':'1F528','🐘':'1F418','🌧️':'1F327','🕊️':'1F54A','🕳️':'1F573',
        '🤗':'1F917','🧀':'1F9C0','😠':'1F620','🎯':'1F3AF','🎉':'1F389','🏖️':'1F3D6',
        '📣':'1F4E3','✝️':'271D','🫏':'1FACF','🪦':'1FAA6','🏛️':'1F3DB','😇':'1F607',
        '🌱':'1F331','🐦':'1F426','🌵':'1F335','⚔️':'2694','🎵':'1F3B5','🗣️':'1F5E3',
        '🎲':'1F3B2','✨':'2728','🔒':'1F512','🥇':'1F947','🥈':'1F948','🥉':'1F949',
        '💎':'1F48E','🧭':'1F9ED','🎮':'1F3AE','🏆':'1F3C6','📗':'1F4D7','🏅':'1F3C5',
        '🔍':'1F50D','🎬':'1F3AC','🎁':'1F381','🏁':'1F3C1','📚':'1F4DA','❓':'2753',
        '🧩':'1F9E9','🍇':'1F347'
    };
    // BASE ya apunta a /assets/ (derivado del src de juegos.js). OMV invalida
    // la caché de 1 mes del .htaccess cuando cambien las imágenes.
    var OMV = '2';
    function omoji(e) {
        var c = OMOJI[e];
        return c
            ? '<img class="omoji" src="' + BASE + 'omoji/' + c + '.svg?v=' + OMV +
              '" alt="" loading="lazy" onerror="this.outerHTML=this.dataset.f" data-f="' + e + '">'
            : e;
    }
    // Ilustración descargada: ref "bibleimg:jonas-a" (.jpg) o "arkset:noe" (.png).
    // Si falta la imagen, cae al emoji OpenMoji.
    function bimg(ref, emoji) {
        var m = /^(bibleimg|arkset):([0-9a-z_-]+)$/i.exec(ref || '');
        if (!m) { return omoji(emoji || ''); }
        var ext = m[1] === 'arkset' ? 'png' : 'jpg';
        // bimg-png = figura con fondo transparente → contain; jpg = escena → cover
        return '<img class="bimg' + (ext === 'png' ? ' bimg-png' : '') +
            '" src="' + BASE + m[1] + '/' + m[2] + '.' + ext + '?v=' + OMV +
            '" alt="" loading="lazy" onerror="this.outerHTML=this.dataset.f" data-f="' + esc(emoji || '') + '">';
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
    // Ruido blanco filtrado con barrido — base de whoosh/flap/pop sin archivos
    function noise(t0, dur, f0, f1, vol) {
        var c = ctx();
        if (!c) { return; }
        var n = Math.max(64, Math.floor(c.sampleRate * dur));
        var buf = c.createBuffer(1, n, c.sampleRate);
        var ch = buf.getChannelData(0);
        for (var i = 0; i < n; i++) { ch[i] = Math.random() * 2 - 1; }
        var src = c.createBufferSource(); src.buffer = buf;
        var fl = c.createBiquadFilter();
        fl.type = 'bandpass';
        fl.frequency.setValueAtTime(f0, c.currentTime + t0);
        fl.frequency.exponentialRampToValueAtTime(f1, c.currentTime + t0 + dur);
        fl.Q.value = .9;
        var g = c.createGain();
        g.gain.setValueAtTime(vol, c.currentTime + t0);
        g.gain.exponentialRampToValueAtTime(.001, c.currentTime + t0 + dur);
        src.connect(fl); fl.connect(g); g.connect(c.destination);
        src.start(c.currentTime + t0);
        src.stop(c.currentTime + t0 + dur + .02);
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
                [1568, 2093].forEach(function (f, i) { tone(f, .55 + i * .1, .22, 'sine', .08); });
            }
            else if (kind === 'tick') { tone(880, 0, .04, 'square', .06); }
            else if (kind === 'pop') { noise(0, .07, 1400, 3200, .13); tone(880, 0, .05, 'sine', .08); }
            else if (kind === 'flap') { noise(0, .13, 420, 1900, .11); }
            else if (kind === 'whoosh') { noise(0, .3, 300, 2400, .1); }
            else if (kind === 'sparkle') {
                [1568, 1976, 2637].forEach(function (f, i) { tone(f, i * .05, .15, 'sine', .11); });
            }
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
            s.innerHTML = omoji('⭐'); s.className = 'bfj-fly';
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
            s.innerHTML = omoji('⭐'); s.className = 'bfj-fly bfj-fly-sm';
            s.style.left = (cx - 8) + 'px'; s.style.top = (cy - 8) + 'px';
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
        var liBefore = levelInfo();
        BFJ.stars.add(o.slug, o.stars);
        if (o.perfect) { wkGet().perfects++; save(); }
        var mnews = missionCheck();
        var liAfter = levelInfo();
        var lvUp = liAfter.name !== liBefore.name;
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
        ov.setAttribute('aria-label', o.title || T('¡Bien hecho!'));
        ov.innerHTML =
            '<div class="bfj-ovcard bfj-pop">' +
            '<div class="bfj-ovemoji">' + omoji(o.emoji || '🎉') + '</div>' +
            '<h2>' + esc(o.title || T('¡Bien hecho!')) + '</h2>' +
            '<div class="bfj-ovstars">' + (o.stars > 0
                ? '⭐'.repeat(Math.min(o.stars, 10)) : '☆') + '</div>' +
            '<p class="bfj-ovpts">+' + o.stars + ' ' + (o.stars === 1 ? T('estrella') : T('estrellas')) + '</p>' +
            (dBonus ? '<div class="bfj-ovdaily">' + T('🗓 ¡Desafío del día! ⭐×2') + '</div>' : '') +
            (lvUp ? '<div class="bfj-ovlvl bfj-pop">' + T('🎖 ¡Subiste de nivel!') + '<br><strong>' +
                esc(liAfter.name) + '</strong></div>' : '') +
            (mnews.length ? '<div class="bfj-ovmision bfj-pop">' +
                mnews.map(function (m) {
                    return T('🎯 Misión: ') + esc(m.name) + ' <b>+' + MISION_REWARD + '⭐</b>';
                }).join('<br>') + '</div>' : '') +
            (o.extra ? '<p class="bfj-ovextra">' + esc(o.extra) + '</p>' : '') +
            (o.html ? '<div class="bfj-ovhtml">' + o.html + '</div>' : '') +
            (news.length ? '<div class="bfj-ovstick bfj-pop">' + T('🎁 ¡Sticker nuevo!') + '<br>' +
                news.map(function (s) {
                    return '<span class="bfj-stick"><i class="bfj-gift" aria-hidden="true">' + omoji('🎁') + '</i> ' + esc(s.name) +
                        '<i class="bfj-stemo" hidden>' + omoji(s.emoji) + '</i></span>';
                }).join('') +
                '</div>' : '') +
            '<div class="bfj-ovbtns">' +
            (o.onAgain ? '<button type="button" class="jbtn jbtn-main" data-c="again">' +
                esc(o.againLabel || T('🔁 Otra ronda')) + '</button>' : '') +
            '<button type="button" class="jbtn jbtn-ghost" data-c="hub">🎮 ' + T('Juegos') + '</button>' +
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
                    gift.innerHTML = emo.innerHTML;
                    gift.classList.remove('bfj-gift'); gift.classList.add('st-pop');
                }, 850 + i * 420);
            });
        } else {
            ov.querySelectorAll('.bfj-stick').forEach(function (el) {
                var gift = el.querySelector('.bfj-gift'), emo = el.querySelector('.bfj-stemo');
                if (gift && emo) { gift.innerHTML = emo.innerHTML; }
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
        if (lvUp) {
            snd('sparkle');
            setTimeout(function () { confetti(1200); }, 650);
        }
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
        omoji: omoji, bimg: bimg,
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
                wkGet().stars += (n | 0);
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
            var w = wkGet();
            w.plays[slug] = (w.plays[slug] || 0) + 1;
            save();
        },
        stickers: {
            all: STICKERS,
            mine: function () { return data.stickers; },
            check: checkStickers
        },
        // Niveles por juego (versiculo): vl[slug] = niveles superados,
        // vlb[slug][nivel] = mejor puntaje del nivel
        levels: {
            passed: function (slug) { return (data.vl[slug] || 0); },
            best: function (slug, idx) { return ((data.vlb[slug] || {})[idx]) || 0; },
            pass: function (slug, idx, ok) {
                if (!data.vlb[slug]) { data.vlb[slug] = {}; }
                if (ok > (data.vlb[slug][idx] || 0)) { data.vlb[slug][idx] = ok; }
                if (idx + 1 > (data.vl[slug] || 0)) { data.vl[slug] = idx + 1; wkGet().levels++; }
                save();
            }
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
    BFJ.T = T; // traducción ES→idioma activo (los juegos la usan como BFJ.T)
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
                    ? li.t + '/' + li.next + ' ' + T('⭐ para el siguiente nivel')
                    : li.t + ' ' + T('⭐ — ¡nivel máximo!');
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
                if (ds) { ds.textContent = T('✅ ¡Hecho!'); }
                var dh = document.getElementById('dailyHint');
                if (dh) { dh.textContent = T('Vuelve mañana por otro desafío'); }
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
                    if (!(data.plays[slug] || 0) && b) { b.textContent = T('✨ ¡Nuevo!'); }
                    if (!nowMarked) { node.classList.add('is-now'); nowMarked = true; }
                    else { node.classList.add('is-todo'); }
                }
            });
            // Mapa de progreso: contador por región (juegos con ⭐ de esa zona)
            document.querySelectorAll('.jh-region[data-reg-slugs]').forEach(function (reg) {
                var slugs = reg.getAttribute('data-reg-slugs').split(',');
                var done = 0;
                slugs.forEach(function (s) { if (BFJ.stars.of(s) > 0) { done++; } });
                var badge = reg.querySelector('.jh-region-n');
                if (badge) {
                    badge.hidden = false;
                    badge.textContent = done + '/' + slugs.length;
                }
                if (done === slugs.length) { reg.classList.add('done'); }
            });
            // Sendero SVG: curva suave que une anillos y banners hasta la meta
            var jhPath = document.querySelector('.jh-path');
            var jhTrail = document.getElementById('jhTrail');
            if (jhPath && jhTrail) {
                var drawTrail = function () {
                    var pb = jhPath.getBoundingClientRect();
                    var pts = [];
                    jhPath.querySelectorAll('.jh-node-ring, .jh-region-tag').forEach(function (n) {
                        var r = n.getBoundingClientRect();
                        pts.push([r.left + r.width / 2 - pb.left, r.top + r.height / 2 - pb.top]);
                    });
                    if (pts.length < 2) { return; }
                    var d = 'M' + pts[0][0].toFixed(1) + ' ' + pts[0][1].toFixed(1);
                    for (var i = 1; i < pts.length; i++) {
                        var p0 = pts[i - 1], p1 = pts[i];
                        var my = ((p0[1] + p1[1]) / 2).toFixed(1);
                        d += ' C' + p0[0].toFixed(1) + ' ' + my + ' ' +
                             p1[0].toFixed(1) + ' ' + my + ' ' +
                             p1[0].toFixed(1) + ' ' + p1[1].toFixed(1);
                    }
                    // tramo final hasta la bandera 🏁 al pie del camino
                    var last = pts[pts.length - 1];
                    d += ' C' + last[0].toFixed(1) + ' ' + (last[1] + 45).toFixed(1) + ' ' +
                         (pb.width / 2).toFixed(1) + ' ' + (pb.height - 6).toFixed(1) + ' ' +
                         (pb.width / 2).toFixed(1) + ' ' + pb.height.toFixed(1);
                    jhTrail.setAttribute('viewBox', '0 0 ' + pb.width + ' ' + pb.height);
                    var ps = jhTrail.querySelectorAll('path');
                    for (var q = 0; q < ps.length; q++) { ps[q].setAttribute('d', d); }
                };
                drawTrail();
                window.addEventListener('resize', drawTrail);
            }
            // Misiones de la semana: rellena la tarjeta y otorga las cumplidas
            var mList = document.getElementById('missionList');
            if (mList) {
                var granted = missionCheck();
                var wkNow = weekKey(), wkNow2 = wkGet();
                if (granted.length) {
                    ts.textContent = BFJ.stars.total();
                    if (!reducedMotion()) { confetti(); }
                }
                mList.innerHTML = weekMissions().map(function (m) {
                    var done = data.missions[m.id] === wkNow;
                    var p = Math.min(m.need, m.pro(wkNow2));
                    return '<div class="jh-mis' + (done ? ' done' : '') + '">' +
                        '<span class="jh-mis-i" aria-hidden="true">' + m.emoji + '</span>' +
                        '<span class="jh-mis-t"><strong>' + esc(m.name) + '</strong>' +
                        '<i class="jh-mis-bar"><b style="width:' + Math.round(p / m.need * 100) + '%"></b></i></span>' +
                        '<span class="jh-mis-n">' +
                        (done ? '✅ +' + MISION_REWARD + '⭐' : p + '/' + m.need) + '</span></div>';
                }).join('');
            }
            // Álbum de stickers: por hitos ya ganados (evalúa sobre historial)
            checkStickers({});
            var wall = document.getElementById('stickerWall');
            if (wall) {
                var mine = BFJ.stickers.mine();
                wall.innerHTML = STICKERS.map(function (s) {
                    var got = mine.indexOf(s.id) >= 0;
                    return '<div class="st' + (got ? ' got' : '') + '" title="' + esc(s.name) + '">' +
                        '<span>' + (got ? omoji(s.emoji) : '❓') + '</span>' +
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
                        ? T('🗓 Desafío completado hoy')
                        : T('🗓 Desafío del día · ⭐×2');
                    head.appendChild(tag);
                }
            }
            if (BFJ.games[slug]) {
                BFJ._t0 = Date.now(); // para métrica game_s (tiempo por ronda)
                BFJ.games[slug](app);
            } else {
                app.innerHTML = '<section class="card notice"><p>' + T('Este juego está en camino 🔧') + '</p></section>';
            }
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
