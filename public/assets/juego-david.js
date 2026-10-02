// David y las Ovejas — tower-defense táctil bíblico (1 Sam 17:34-49).
// El jugador toca la pantalla para lanzar piedras con la honda y proteger
// el rebaño de leones y osos; la última ronda es contra Goliat.
// Canvas 2D a devicePixelRatio + fallback touchstart/keyCode (iOS <13).
BFJ.define('david', function (el) {
    var ROUNDS = [
        {
            name: 'Ronda 1 · El león',
            ref: '1 Samuel 17:34-35',
            desc: '«Tu siervo apacentaba las ovejas de su padre… salió tras el león y lo hirió.»',
            waves: [['lion', 4]], spawnEvery: 2600
        },
        {
            name: 'Ronda 2 · El oso',
            ref: '1 Samuel 17:36',
            desc: '«León y oso, tu siervo los mató.»',
            waves: [['lion', 2], ['bear', 3]], spawnEvery: 2300
        },
        {
            name: 'Ronda 3 · Goliat',
            ref: '1 Samuel 17:45-49',
            desc: '«Tú vienes con espada; yo vengo en el nombre de Jehová.»',
            waves: [['lion', 2], ['bear', 2], ['goliath', 1]], spawnEvery: 2500
        },
        {
            name: 'Libre · El buen pastor',
            ref: 'Salmo 23',
            desc: 'Jehová es mi pastor. ¿Cuánto aguantas guardando el rebaño?',
            endless: true, waves: [['lion', 4], ['bear', 3]], spawnEvery: 2100
        }
    ];
    var KINDS = {
        lion:    { hp: 2, speed: 30, r: 15, score: 1, emoji: '🦁' },
        bear:    { hp: 3, speed: 19, r: 19, score: 2, emoji: '🐻' },
        goliath: { hp: 6, speed: 9,  r: 27, score: 5, emoji: '🗿' }
    };
    var SHEEP_N = 5;

    var passed = BFJ.levels.passed('david');
    var ri = Math.min(passed, 3);
    var best = 0;
    try { best = parseInt(localStorage.getItem('bf_dav_best') || '0', 10) || 0; } catch (e) {}

    function menuHTML() {
        var r = ROUNDS[ri];
        return (
            '<p class="pn-emo" aria-hidden="true">🐑</p>' +
            '<p class="pn-t">' + BFJ.esc(r.name) + '</p>' +
            '<p class="pn-s">' + BFJ.esc(r.desc) + '</p>' +
            '<p class="pn-ref">📖 ' + r.ref + '</p>' +
            '<p class="pn-goal">' + (r.endless
                ? '🏁 Sin meta — defiende todo lo que puedas'
                : '🏁 Repele a todos los depredadores') + '</p>' +
            (best ? '<p class="pn-best">✨ Tu récord: ' + best + ' enemigos</p>' : '') +
            '<button type="button" class="jbtn jbtn-main" id="dvGo">▶ ¡A defender!</button>' +
            '<p class="pn-k">👆 Toca a los depredadores para lanzar piedras</p>'
        );
    }

    el.innerHTML =
        '<div class="pn-stage" id="dvStage">' +
        '<canvas class="pn-cv" id="dvCv" width="480" height="320" ' +
        'aria-label="David y las Ovejas: toca los depredadores para lanzarles piedras"></canvas>' +
        '<div class="pn-hud" aria-hidden="true">' +
        '<span id="dvSheep">🐑 ' + SHEEP_N + '</span><span id="dvKills">⚔️ 0</span>' +
        '</div>' +
        '<div class="pn-hint" id="dvHint" hidden>👆 ¡Toca al depredador!</div>' +
        '<div class="pn-menu" id="dvMenu"></div>' +
        '</div>';

    var cv = document.getElementById('dvCv');
    var ctx = cv.getContext('2d');
    var stage = document.getElementById('dvStage');
    var menu = document.getElementById('dvMenu');
    var hint = document.getElementById('dvHint');
    var hudSheep = document.getElementById('dvSheep');
    var hudKills = document.getElementById('dvKills');
    var W = 480, H = 320;
    var DPR = Math.min(2, window.devicePixelRatio || 1);
    cv.width = W * DPR; cv.height = H * DPR;
    ctx.scale(DPR, DPR);

    var raf = null, playing = false, over = false, won = false;
    var sheep, foes, stones, parts, popups;
    var t, kills, reload, queue, spawnIn, armT;

    function round() { return ROUNDS[ri]; }

    function reset() {
        sheep = [];
        for (var i = 0; i < SHEEP_N; i++) {
            sheep.push({
                x: 30 + Math.random() * 110, y: 150 + Math.random() * 140,
                tx: 0, ty: 0, ph: Math.random() * 6.28, alive: true
            });
        }
        foes = []; stones = []; parts = []; popups = [];
        // cola de enemigos por ronda
        queue = [];
        round().waves.forEach(function (wv) {
            for (var i = 0; i < wv[1]; i++) { queue.push(wv[0]); }
        });
        BFJ.shuffle(queue);
        // el jefe siempre al final
        queue.sort(function (a, b) { return (a === 'goliath' ? 1 : 0) - (b === 'goliath' ? 1 : 0); });
        t = 0; kills = 0; reload = 0; spawnIn = 1200; armT = 0;
        hudSheep.textContent = '🐑 ' + sheep.length;
        hudKills.textContent = '⚔️ 0';
    }

    function spawnFoe() {
        var kind = queue.shift() || (round().endless ? (Math.random() < .65 ? 'lion' : 'bear') : null);
        if (!kind) { return; }
        var k = KINDS[kind];
        foes.push({
            kind: kind, hp: k.hp, x: W + 30, y: 150 + Math.random() * 140,
            flash: 0, flee: false, ph: Math.random() * 6.28
        });
    }

    function sling(px, py) {
        if (!playing || over || won) { return; }
        if (reload > 0) { return; }
        reload = .38;
        armT = .3;
        stones.push({ x: 84, y: 178, tx: px, ty: py, d: 0, tot: 0 });
        var s = stones[stones.length - 1];
        var dx = px - s.x, dy = py - s.y;
        s.tot = Math.sqrt(dx * dx + dy * dy) / 620; // seg aprox de vuelo
        BFJ.snd('whoosh');
    }

    function popup(x, y, txt) { popups.push({ x: x, y: y, txt: txt, life: 1 }); }

    function hurtFoe(f) {
        f.hp--; f.flash = .18;
        for (var i = 0; i < 6; i++) {
            parts.push({ x: f.x, y: f.y, vx: (Math.random() - .5) * 130,
                vy: -Math.random() * 110, s: 2 + Math.random() * 2.4, life: 1, c: '#d8b26a' });
        }
        if (f.hp <= 0) {
            kills++;
            hudKills.textContent = '⚔️ ' + kills;
            popup(f.x, f.y - f.r - 8, '+1 ⚔️');
            BFJ.snd('pop');
            foes.splice(foes.indexOf(f), 1);
        } else {
            BFJ.snd('click');
        }
    }

    function die() {
        if (over || won) { return; }
        over = true;
        BFJ.snd('bad'); BFJ.shake(stage);
        setTimeout(function () { finish(false); }, 900);
    }

    function winRound() {
        if (won || over) { return; }
        won = true;
        BFJ.snd('win');
        setTimeout(function () { finish(true); }, 800);
    }

    function finish(success) {
        cancelAnimationFrame(raf);
        playing = false;
        var left = 0;
        sheep.forEach(function (s) { if (s.alive) { left++; } });
        var isRecord = kills > best;
        if (isRecord) {
            best = kills;
            try { localStorage.setItem('bf_dav_best', String(kills)); } catch (e) {}
        }
        var stars = Math.min(10, kills + (success ? 2 : 0) + left);
        if (success && !round().endless) {
            BFJ.levels.pass('david', ri, kills);
        }
        var title = success
            ? (ri === 2 ? '¡El pastor venció al gigante!' : '¡Rebaño a salvo!')
            : 'El rebaño se dispersó…';
        BFJ.celebrate({
            slug: 'david', stars: stars,
            emoji: success ? '🏆' : '🐑', title: title,
            extra: kills + ' depredadores vencidos · ' + left + '/' + SHEEP_N + ' ovejas' +
                (isRecord ? ' · ✨ ¡récord!' : ''),
            perfect: success && left === SHEEP_N,
            onAgain: function () { startRound(); }
        });
    }

    function step(ms) {
        var dt = Math.min(.05, ms / 1000);
        t += ms; reload = Math.max(0, reload - dt); armT = Math.max(0, armT - dt);
        if (over || won) { return; }

        // ovejas deambulan por el prado izquierdo
        sheep.forEach(function (s) {
            if (!s.alive) { return; }
            s.ph += dt * .8;
            if (s.tx === 0 || Math.random() < .004) {
                s.tx = 25 + Math.random() * 115;
                s.ty = 150 + Math.random() * 140;
            }
            s.x += (s.tx - s.x) * dt * .6;
            s.y += (s.ty - s.y) * dt * .6 + Math.sin(s.ph * 4) * .18;
        });

        // oleada
        spawnIn -= ms;
        if (spawnIn <= 0 && (queue.length || round().endless)) {
            spawnFoe();
            var gap = round().spawnEvery - Math.min(900, kills * 60);
            spawnIn = round().endless && kills > 12 ? gap * .8 : gap;
        }

        // enemigos avanzan a la oveja viva más cercana; al alcanzarla huyen
        for (var i = foes.length - 1; i >= 0; i--) {
            var f = foes[i], k = KINDS[f.kind];
            f.flash = Math.max(0, f.flash - dt);
            f.ph += dt;
            if (f.flee) {
                f.x += k.speed * 3.2 * dt;
                if (f.x > W + 40) { foes.splice(i, 1); }
                continue;
            }
            var target = null, bd = 1e9;
            sheep.forEach(function (s) {
                if (!s.alive) { return; }
                var dx = s.x - f.x, dy = s.y - f.y, d = dx * dx + dy * dy;
                if (d < bd) { bd = d; target = s; }
            });
            if (!target) { f.flee = true; continue; }
            var dist = Math.sqrt(bd);
            f.x += (target.x - f.x) / dist * k.speed * dt;
            f.y += (target.y - f.y) / dist * k.speed * dt;
            if (dist < k.r + 12) {
                target.alive = false;
                f.flee = true;
                hudSheep.textContent = '🐑 ' + sheep.filter(function (s) { return s.alive; }).length;
                popup(target.x, target.y - 14, '💔');
                BFJ.snd('bad');
                for (var j = 0; j < 8; j++) {
                    parts.push({ x: target.x, y: target.y, vx: (Math.random() - .5) * 120,
                        vy: -Math.random() * 100, s: 2 + Math.random() * 2, life: 1, c: '#fff' });
                }
                if (sheep.every(function (s) { return !s.alive; })) { die(); return; }
            }
        }

        // piedras: vuelo con arco y daño al llegar
        for (var si = stones.length - 1; si >= 0; si--) {
            var st = stones[si];
            st.d += dt;
            if (st.d >= st.tot) {
                stones.splice(si, 1);
                var hitFoe = null, hd = 1e9;
                foes.forEach(function (f) {
                    var dx = f.x - st.tx, dy = f.y - st.ty, d = dx * dx + dy * dy;
                    var rr = (KINDS[f.kind].r + 10) * (KINDS[f.kind].r + 10);
                    if (d < rr && d < hd) { hd = d; hitFoe = f; }
                });
                if (hitFoe) { hurtFoe(hitFoe); }
                else { popup(st.tx, st.ty - 6, '·'); }
            }
        }

        for (var pi = parts.length - 1; pi >= 0; pi--) {
            var p = parts[pi];
            p.x += p.vx * dt; p.y += p.vy * dt; p.vy += 380 * dt; p.life -= dt * 1.5;
            if (p.life <= 0) { parts.splice(pi, 1); }
        }
        for (var qi = popups.length - 1; qi >= 0; qi--) {
            popups[qi].y -= 22 * dt; popups[qi].life -= dt * 1.1;
            if (popups[qi].life <= 0) { popups.splice(qi, 1); }
        }

        // ronda ganada: sin cola, sin enemigos en pantalla
        if (!round().endless && !queue.length && !foes.length && playing) { winRound(); }
    }

    // ============================ Dibujo ===================================
    function drawSheep(s) {
        ctx.save(); ctx.translate(s.x, s.y);
        var hop = Math.abs(Math.sin(s.ph * 4)) * 1.6;
        ctx.translate(0, -hop);
        ctx.fillStyle = '#7a5b3a';                       // patas
        ctx.fillRect(-6, 5, 2.5, 5); ctx.fillRect(4, 5, 2.5, 5);
        ctx.fillStyle = '#fdfdfb';                       // lana (3 puffs)
        ctx.beginPath();
        ctx.arc(-4, 0, 6, 0, 7); ctx.arc(3, -2, 7, 0, 7); ctx.arc(6, 3, 5.5, 0, 7);
        ctx.fill();
        ctx.fillStyle = '#4a3a28';                       // cabeza
        ctx.beginPath(); ctx.arc(10, -1, 4, 0, 7); ctx.fill();
        ctx.fillStyle = '#fff';
        ctx.beginPath(); ctx.arc(11, -2, 1.1, 0, 7); ctx.fill();
        ctx.restore();
    }

    function drawFoe(f) {
        var k = KINDS[f.kind];
        ctx.save(); ctx.translate(f.x, f.y);
        var wob = Math.sin(f.ph * 6) * 1.4;
        ctx.translate(0, wob * .3);
        if (f.flee) { ctx.scale(-1, 1); }
        if (f.flash > 0) { ctx.globalAlpha = .55 + Math.sin(t * .06) * .3; }

        if (f.kind === 'goliath') {
            // gigante: lanza, escudo, casco
            ctx.fillStyle = '#8d6e63';
            ctx.fillRect(-7, -18, 14, 30);                    // torso
            ctx.fillStyle = '#5d4037';
            ctx.fillRect(-7, -18, 14, 8);                     // coraza
            ctx.fillStyle = '#d7a06a';
            ctx.beginPath(); ctx.arc(0, -24, 7, 0, 7); ctx.fill(); // cabeza
            ctx.fillStyle = '#4e342e';
            ctx.beginPath(); ctx.arc(0, -27, 7.4, Math.PI, 0); ctx.fill(); // casco
            ctx.strokeStyle = '#795548'; ctx.lineWidth = 3;
            ctx.beginPath(); ctx.moveTo(9, -8); ctx.lineTo(20, -30); ctx.stroke(); // lanza
            ctx.fillStyle = '#6d4c41';
            ctx.beginPath(); ctx.arc(-11, -2, 9, 0, 7); ctx.fill();  // escudo
            ctx.fillStyle = '#3e2723';
            ctx.fillRect(-6, 12, 4.5, 9); ctx.fillRect(2, 12, 4.5, 9); // piernas
        } else {
            var body = f.kind === 'lion' ? '#d8912f' : '#6d4c41';
            var dark = f.kind === 'lion' ? '#a5641d' : '#4e342e';
            ctx.fillStyle = body;
            ctx.beginPath(); ctx.ellipse(-2, 0, k.r, k.r * .68, 0, 0, 7); ctx.fill();
            ctx.beginPath(); ctx.arc(-k.r - 2, -4, k.r * .62, 0, 7); ctx.fill(); // cabeza
            if (f.kind === 'lion') {                                   // melena
                ctx.fillStyle = dark;
                ctx.beginPath(); ctx.arc(-k.r - 2, -4, k.r * .62 + 4, 0, 7); ctx.fill();
                ctx.fillStyle = body;
                ctx.beginPath(); ctx.arc(-k.r - 2, -4, k.r * .62, 0, 7); ctx.fill();
            } else {                                                   // orejas oso
                ctx.fillStyle = dark;
                ctx.beginPath(); ctx.arc(-k.r - 7, -11, 3.4, 0, 7); ctx.fill();
                ctx.beginPath(); ctx.arc(-k.r + 3, -11, 3.4, 0, 7); ctx.fill();
            }
            ctx.fillStyle = '#2b1c12';                                 // ojo + hocico
            ctx.beginPath(); ctx.arc(-k.r - 5, -6, 1.5, 0, 7); ctx.fill();
            ctx.beginPath(); ctx.arc(-k.r - 9, -1, 2.2, 0, 7); ctx.fill();
            ctx.fillStyle = dark;                                      // patas
            ctx.fillRect(-k.r + 2, k.r * .6, 4, 7);
            ctx.fillRect(k.r - 12, k.r * .6, 4, 7);
        }
        // vida
        ctx.globalAlpha = 1;
        ctx.fillStyle = 'rgba(0,0,0,.25)';
        ctx.fillRect(-k.r, -k.r - 14, k.r * 2, 4);
        ctx.fillStyle = f.hp > k.hp / 2 ? '#6fbf5a' : '#e05252';
        ctx.fillRect(-k.r, -k.r - 14, k.r * 2 * (f.hp / k.hp), 4);
        ctx.restore();
    }

    function drawDavid() {
        ctx.save(); ctx.translate(84, 178);
        var aim = armT > 0 ? -Math.sin(armT * 10) * .8 : 0;
        ctx.strokeStyle = '#7a5b3a'; ctx.lineWidth = 4; ctx.lineCap = 'round';
        ctx.beginPath(); ctx.moveTo(0, 6); ctx.lineTo(0, 18); ctx.stroke();  // piernas→túnica
        ctx.fillStyle = '#b8895a';
        ctx.beginPath(); ctx.arc(0, -4, 9, 0, 7); ctx.fill();                 // torso
        ctx.fillStyle = '#d7a06a';
        ctx.beginPath(); ctx.arc(0, -15, 6, 0, 7); ctx.fill();                // cabeza
        ctx.fillStyle = '#4a2e1a';
        ctx.beginPath(); ctx.arc(0, -17.5, 6.4, Math.PI, 0); ctx.fill();      // pelo
        // brazo con honda girando
        ctx.save(); ctx.translate(4, -8); ctx.rotate(-.7 + aim);
        ctx.strokeStyle = '#d7a06a'; ctx.lineWidth = 3.4;
        ctx.beginPath(); ctx.moveTo(0, 0); ctx.lineTo(12, 0); ctx.stroke();
        ctx.strokeStyle = '#5d4037'; ctx.lineWidth = 1.6;
        ctx.beginPath(); ctx.moveTo(12, 0); ctx.lineTo(19, -7); ctx.moveTo(12, 0); ctx.lineTo(19, 5); ctx.stroke();
        ctx.restore();
        ctx.restore();
    }

    function draw() {
        // cielo + prado
        var sky = ctx.createLinearGradient(0, 0, 0, H);
        sky.addColorStop(0, '#a8d0ee'); sky.addColorStop(.55, '#d9ecf9');
        ctx.fillStyle = sky; ctx.fillRect(0, 0, W, H);
        ctx.fillStyle = '#8fbf6a';
        ctx.fillRect(0, 120, W, H - 120);
        ctx.fillStyle = '#7cae55';
        ctx.fillRect(0, 120, W, 8);
        ctx.fillStyle = 'rgba(60,90,40,.25)';
        for (var gx = 0; gx < W; gx += 36) {
            ctx.fillRect(gx + ((t * .008) % 36), 190, 3, 16);
            ctx.fillRect(gx + 18 - ((t * .008) % 36), 250, 3, 16);
        }
        // zona del rebaño (cerco)
        ctx.strokeStyle = 'rgba(122,91,58,.5)'; ctx.lineWidth = 3;
        ctx.setLineDash ? ctx.setLineDash([6, 8]) : 0;
        ctx.beginPath(); ctx.arc(85, 225, 92, 0, 7); ctx.stroke();
        if (ctx.setLineDash) { ctx.setLineDash([]); }

        sheep.forEach(function (s) { if (s.alive) { drawSheep(s); } });
        foes.forEach(drawFoe);
        drawDavid();

        // piedras en vuelo (parábola)
        ctx.fillStyle = '#8d8d94';
        stones.forEach(function (st) {
            var u = Math.min(1, st.d / st.tot);
            var sx = st.x + (st.tx - st.x) * u;
            var sy = st.y + (st.ty - st.y) * u - Math.sin(u * Math.PI) * 26;
            ctx.beginPath(); ctx.arc(sx, sy, 4, 0, 7); ctx.fill();
        });

        parts.forEach(function (p) {
            ctx.globalAlpha = Math.max(0, p.life);
            ctx.fillStyle = p.c;
            ctx.beginPath(); ctx.arc(p.x, p.y, p.s, 0, 7); ctx.fill();
        });
        ctx.globalAlpha = 1;
        popups.forEach(function (p) {
            ctx.globalAlpha = Math.max(0, p.life);
            ctx.font = '700 14px sans-serif'; ctx.textAlign = 'center';
            ctx.fillStyle = '#fff';
            ctx.strokeStyle = 'rgba(0,0,0,.4)'; ctx.lineWidth = 3;
            ctx.strokeText(p.txt, p.x, p.y); ctx.fillText(p.txt, p.x, p.y);
        });
        ctx.globalAlpha = 1;
    }

    var last = 0;
    function loop(now) {
        if (!last) { last = now; }
        var ms = now - last; last = now;
        if (playing && !over && !won) { step(ms); }
        draw();
        raf = requestAnimationFrame(loop);
    }

    // ============================ Controles ================================
    function ptPos(ev) {
        var r = cv.getBoundingClientRect();
        var cx, cy;
        if (ev.touches && ev.touches.length) { cx = ev.touches[0].clientX; cy = ev.touches[0].clientY; }
        else { cx = ev.clientX; cy = ev.clientY; }
        return [(cx - r.left) / r.width * W, (cy - r.top) / r.height * H];
    }
    function onDown(ev) {
        var p = ptPos(ev);
        if (!playing) { return; }
        ev.preventDefault();
        sling(p[0], p[1]);
    }
    cv.addEventListener('mousedown', onDown);
    cv.addEventListener('touchstart', onDown, { passive: false });

    function startRound() {
        reset();
        over = false; won = false; playing = true;
        menu.hidden = true;
        hint.hidden = false;
        setTimeout(function () { hint.hidden = true; }, 2600);
        last = 0;
        raf = requestAnimationFrame(loop);
    }

    menu.innerHTML = menuHTML();
    var go = document.getElementById('dvGo');
    if (go) { go.addEventListener('click', function () { BFJ.snd('click'); startRound(); }); }
    draw(); // primer frame estático detrás del menú
});
