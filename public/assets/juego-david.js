// David y las Ovejas — tower-defense táctil bíblico (1 Sam 17:34-49).
// El jugador toca la pantalla para lanzar piedras con la honda y proteger
// el rebaño de leones y osos; la última ronda es contra Goliat.
// Canvas 2D a devicePixelRatio + fallback touchstart/keyCode (iOS <13).
BFJ.define('david', function (el) {
    var ROUNDS = [
        {
            name: BFJ.T('Ronda 1 · El león'),
            ref: '1 ' + BFJ.T('Samuel') + ' 17:34-35',
            desc: BFJ.T('«Tu siervo apacentaba las ovejas de su padre… salió tras el león y lo hirió.»'),
            waves: [['lion', 5]], spawnEvery: 2400, burst: 2
        },
        {
            name: BFJ.T('Ronda 2 · El oso'),
            ref: '1 ' + BFJ.T('Samuel') + ' 17:36',
            desc: BFJ.T('«León y oso, tu siervo los mató.»'),
            waves: [['lion', 3], ['bear', 3]], spawnEvery: 2600, burst: 2
        },
        {
            name: BFJ.T('Ronda 3 · Goliat'),
            ref: '1 ' + BFJ.T('Samuel') + ' 17:45-49',
            desc: BFJ.T('«Tú vienes con espada; yo vengo en el nombre de Jehová.»'),
            waves: [['lion', 2], ['bear', 3], ['goliath', 1]], spawnEvery: 2800, burst: 2
        },
        {
            name: BFJ.T('Libre · El buen pastor'),
            ref: BFJ.T('Salmo') + ' 23',
            desc: BFJ.T('Jehová es mi pastor. ¿Cuánto aguantas guardando el rebaño?'),
            endless: true, waves: [['lion', 4], ['bear', 3]], spawnEvery: 2100, burst: 2
        }
    ];
    var KINDS = {
        lion:    { hp: 2, speed: 38, r: 15, score: 1, emoji: '🦁' },
        bear:    { hp: 3, speed: 24, r: 19, score: 2, emoji: '🐻' },
        goliath: { hp: 6, speed: 11, r: 27, score: 5, emoji: '🗿' }
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
                ? BFJ.T('🏁 Sin meta — defiende todo lo que puedas')
                : BFJ.T('🏁 Repele a todos los depredadores')) + '</p>' +
            (best ? '<p class="pn-best">✨ ' + BFJ.T('Tu récord:') + ' ' + best + ' ' + BFJ.T('enemigos') + '</p>' : '') +
            '<button type="button" class="jbtn jbtn-main" id="dvGo">' + BFJ.T('▶ ¡A defender!') + '</button>' +
            '<p class="pn-k">' + BFJ.T('👆 Toca a los depredadores para lanzar piedras') + '</p>'
        );
    }

    el.innerHTML =
        '<div class="pn-stage" id="dvStage">' +
        '<canvas class="pn-cv" id="dvCv" width="480" height="320" ' +
        'aria-label="' + BFJ.T('David y las Ovejas: toca los depredadores para lanzarles piedras') + '"></canvas>' +
        '<div class="pn-hud" aria-hidden="true">' +
        '<span id="dvSheep">🐑 ' + SHEEP_N + '</span><span id="dvKills">⚔️ 0</span>' +
        '</div>' +
        '<div class="pn-hint" id="dvHint" hidden>' + BFJ.T('👆 ¡Toca al depredador!') + '</div>' +
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
            ? (ri === 2 ? BFJ.T('¡El pastor venció al gigante!') : BFJ.T('¡Rebaño a salvo!'))
            : BFJ.T('El rebaño se dispersó…');
        BFJ.celebrate({
            slug: 'david', stars: stars,
            emoji: success ? '🏆' : '🐑', title: title,
            extra: kills + ' ' + BFJ.T('depredadores vencidos') + ' · ' + left + '/' + SHEEP_N + ' ' + BFJ.T('ovejas') +
                (isRecord ? ' · ' + BFJ.T('✨ ¡récord!') : ''),
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

        // oleada: ráfaga de `burst` depredadores a la vez (la presión real
        // viene de objetivos simultáneos, no del intervalo)
        spawnIn -= ms;
        if (spawnIn <= 0 && (queue.length || round().endless)) {
            var burst = round().burst || 1;
            for (var b = 0; b < burst && (queue.length || round().endless); b++) { spawnFoe(); }
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
    function shadow(rx) {
        ctx.fillStyle = 'rgba(30,40,20,.16)';
        ctx.beginPath(); ctx.ellipse(0, 0, rx, rx * .3, 0, 0, 7); ctx.fill();
    }

    function drawSheep(s) {
        ctx.save(); ctx.translate(s.x, s.y);
        ctx.save(); ctx.translate(0, 10); shadow(11); ctx.restore();
        var hop = Math.abs(Math.sin(s.ph * 4)) * 1.6;
        var w = Math.sin(s.ph * 4);                       // paso alterno
        ctx.fillStyle = '#5d4730';                        // patas
        ctx.fillRect(-6 + w * 1.4, 4, 2.4, 6);
        ctx.fillRect(-1 - w * 1.4, 4.6, 2.4, 5.4);
        ctx.fillRect(4 + w * 1.4, 4, 2.4, 6);
        ctx.translate(0, -hop);
        // lana: anillo de puffs sobre núcleo
        ctx.fillStyle = '#f0ede4';
        for (var i = 0; i < 8; i++) {
            var a = i / 8 * 6.283;
            ctx.beginPath(); ctx.arc(Math.cos(a) * 6.6, Math.sin(a) * 4.4 - 1, 4.1, 0, 7); ctx.fill();
        }
        ctx.fillStyle = '#fdfdfb';
        ctx.beginPath(); ctx.arc(0, -1, 6.8, 0, 7); ctx.fill();
        ctx.beginPath(); ctx.arc(-9.5, -1, 2.4, 0, 7); ctx.fill(); // cola
        ctx.fillStyle = '#4a3a28';                        // cabeza
        ctx.beginPath(); ctx.ellipse(11, -2, 4.4, 3.6, 0, 0, 7); ctx.fill();
        ctx.beginPath(); ctx.ellipse(9.5, -5.2, 2.6, 1.4, -.5, 0, 7); ctx.fill(); // oreja
        ctx.fillStyle = '#fff';
        ctx.beginPath(); ctx.arc(12, -2.6, 1.1, 0, 7); ctx.fill();
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
            var gg = Math.sin(f.ph * 5);
            ctx.save(); ctx.translate(0, 22); shadow(20); ctx.restore();
            // piernas en postura ancha + grebas de bronce + sandalias
            ctx.strokeStyle = '#c98f5e'; ctx.lineWidth = 5; ctx.lineCap = 'round';
            ctx.beginPath(); ctx.moveTo(-4, 6); ctx.lineTo(-7 + gg, 17); ctx.stroke();
            ctx.beginPath(); ctx.moveTo(4, 6); ctx.lineTo(8 - gg, 17); ctx.stroke();
            ctx.strokeStyle = '#b8860b'; ctx.lineWidth = 5.4;
            ctx.beginPath(); ctx.moveTo(-6 + gg, 12); ctx.lineTo(-7 + gg, 17); ctx.stroke();
            ctx.beginPath(); ctx.moveTo(7 - gg, 12); ctx.lineTo(8 - gg, 17); ctx.stroke();
            ctx.fillStyle = '#4e342e';
            ctx.fillRect(-10.5 + gg, 17, 6.5, 3.2);
            ctx.fillRect(5.5 - gg, 17, 6.5, 3.2);
            // faldellín de cuero
            ctx.fillStyle = '#6b3a2a';
            ctx.beginPath();
            ctx.moveTo(-8, 1); ctx.lineTo(-9.5, 10); ctx.lineTo(9.5, 10); ctx.lineTo(8, 1);
            ctx.closePath(); ctx.fill();
            // coraza de bronce trapecio con bandas
            ctx.fillStyle = '#c9a227';
            ctx.beginPath();
            ctx.moveTo(-11, -18); ctx.lineTo(-8, 2); ctx.lineTo(8, 2); ctx.lineTo(11, -18);
            ctx.closePath(); ctx.fill();
            ctx.strokeStyle = 'rgba(90,60,20,.55)'; ctx.lineWidth = 1.6;
            for (var ry = -14; ry <= -2; ry += 4) {
                var bw = 10.6 - (ry + 18) * .15;
                ctx.beginPath(); ctx.moveTo(-bw, ry); ctx.lineTo(bw, ry); ctx.stroke();
            }
            // lanza al hombro (atrás, se ve toda)
            ctx.strokeStyle = '#795548'; ctx.lineWidth = 2.8;
            ctx.beginPath(); ctx.moveTo(13, 8 + gg); ctx.lineTo(13, -36 + gg); ctx.stroke();
            ctx.fillStyle = '#9aa0a8';
            ctx.beginPath();
            ctx.moveTo(13, -44 + gg); ctx.lineTo(16.5, -35 + gg); ctx.lineTo(13, -33 + gg);
            ctx.lineTo(9.5, -35 + gg); ctx.closePath(); ctx.fill();
            // brazo trasero sujetándola
            ctx.strokeStyle = '#c98f5e'; ctx.lineWidth = 4.4;
            ctx.beginPath(); ctx.moveTo(7, -12); ctx.lineTo(13, -6 + gg * .5); ctx.stroke();
            // cabeza: rostro, ceño, barba, casco con plumaje
            ctx.fillStyle = '#d7a06a';
            ctx.beginPath(); ctx.arc(0, -24, 6.5, 0, 7); ctx.fill();
            ctx.fillStyle = '#4e342e';
            ctx.beginPath(); ctx.arc(0, -21, 5, .15, Math.PI - .15); ctx.fill(); // barba
            ctx.fillStyle = '#2b1c12';
            ctx.beginPath(); ctx.arc(-3, -25.5, 1.2, 0, 7); ctx.fill();          // ojo
            ctx.strokeStyle = '#2b1c12'; ctx.lineWidth = 1.5;
            ctx.beginPath(); ctx.moveTo(-6, -28); ctx.lineTo(-1.5, -26.5); ctx.stroke(); // ceño
            // casco: cúpula + carrillera + nasal
            ctx.fillStyle = '#c9a227';
            ctx.beginPath(); ctx.arc(0, -27, 7.4, Math.PI, 0); ctx.fill();
            ctx.fillRect(-7.4, -28, 14.8, 2.4);
            ctx.fillRect(-2, -27, 3.4, 5);                                     // nasal
            // plumaje rojo que flota hacia atrás
            ctx.fillStyle = '#a03030';
            ctx.beginPath();
            ctx.ellipse(4, -31 - Math.abs(gg), 8.5, 3.6, -.18, 0, 7); ctx.fill();
            // escudo grande adelante: bronce, borde y umbo
            ctx.fillStyle = '#8d6e63';
            ctx.beginPath(); ctx.arc(-13, -4, 11, 0, 7); ctx.fill();
            ctx.strokeStyle = '#c9a227'; ctx.lineWidth = 2.6;
            ctx.beginPath(); ctx.arc(-13, -4, 11, 0, 7); ctx.stroke();
            ctx.beginPath(); ctx.arc(-13, -4, 5.5, 0, 7); ctx.stroke();
            ctx.fillStyle = '#c9a227';
            ctx.beginPath(); ctx.arc(-13, -4, 3.2, 0, 7); ctx.fill();
        } else if (f.kind === 'bear') {
            var bg = Math.sin(f.ph * 7);
            ctx.save(); ctx.translate(0, k.r + 9); shadow(k.r + 4); ctx.restore();
            var bb = '#7a5230', bd = '#523619', bl = '#a1896d';
            // patas rechonchas en marcha
            ctx.fillStyle = bd;
            ctx.fillRect(-k.r + 1 + bg * 2.4, k.r * .35, 5.5, k.r);
            ctx.fillRect(-k.r + 9 - bg * 2.4, k.r * .45, 5.5, k.r * .9);
            ctx.fillRect(k.r - 12 - bg * 2.4, k.r * .35, 5.5, k.r);
            ctx.fillRect(k.r - 4 + bg * 2.4, k.r * .45, 4.5, k.r * .9);
            // cuerpo rollizo + giba alta
            ctx.fillStyle = bb;
            ctx.beginPath(); ctx.ellipse(-1, 0, k.r, k.r * .74, 0, 0, 7); ctx.fill();
            ctx.fillStyle = bd;
            ctx.beginPath(); ctx.arc(-k.r * .25, -k.r * .48, k.r * .5, Math.PI, 0); ctx.fill();
            // lomito claro
            ctx.fillStyle = '#8d6e63';
            ctx.beginPath(); ctx.ellipse(-1, k.r * .3, k.r * .6, k.r * .3, 0, 0, 7); ctx.fill();
            // rabito
            ctx.fillStyle = bd;
            ctx.beginPath(); ctx.arc(k.r * .85, -2, 2.6, 0, 7); ctx.fill();
            // cabeza grande con orejas redondas
            var bhx = -k.r - 2, bhy = -6;
            ctx.fillStyle = bb;
            ctx.beginPath(); ctx.arc(bhx, bhy, k.r * .58, 0, 7); ctx.fill();
            ctx.fillStyle = bd;
            ctx.beginPath(); ctx.arc(bhx - 3, bhy - 8, 3.4, 0, 7); ctx.fill();
            ctx.beginPath(); ctx.arc(bhx + 5, bhy - 8.4, 3.4, 0, 7); ctx.fill();
            ctx.fillStyle = '#3e2723';
            ctx.beginPath(); ctx.arc(bhx - 3, bhy - 8, 1.7, 0, 7); ctx.fill();
            ctx.beginPath(); ctx.arc(bhx + 5, bhy - 8.4, 1.7, 0, 7); ctx.fill();
            // hocico claro con nariz grande
            ctx.fillStyle = bl;
            ctx.beginPath(); ctx.ellipse(bhx - 8, bhy + 1, 5, 3.8, 0, 0, 7); ctx.fill();
            ctx.fillStyle = '#2b1c12';
            ctx.beginPath(); ctx.arc(bhx - 11.5, bhy - .5, 1.9, 0, 7); ctx.fill();
            ctx.beginPath(); ctx.arc(bhx - 2, bhy - 3.5, 1.5, 0, 7); ctx.fill();  // ojo
        } else {
            var lg = Math.sin(f.ph * 9);
            var lb = '#d8912f', ld = '#8f5410', lm = '#b26e1e';
            ctx.save(); ctx.translate(0, k.r + 9); shadow(k.r + 4); ctx.restore();
            // cola con mechón que se agita
            ctx.strokeStyle = lb; ctx.lineWidth = 2.4; ctx.lineCap = 'round';
            ctx.beginPath(); ctx.moveTo(k.r - 2, -4);
            ctx.quadraticCurveTo(k.r + 9, -10 - lg * 2, k.r + 5, -16 - lg);
            ctx.stroke();
            ctx.fillStyle = ld;
            ctx.beginPath(); ctx.arc(k.r + 5, -16 - lg, 3, 0, 7); ctx.fill();
            // patas en marcha alterna
            ctx.fillStyle = lm;
            ctx.fillRect(-k.r + 3 + lg * 2, k.r * .5, 4, k.r * .8);
            ctx.fillRect(k.r - 12 - lg * 2, k.r * .5, 4, k.r * .8);
            // cuerpo + panza clara
            ctx.fillStyle = lb;
            ctx.beginPath(); ctx.ellipse(-1, 0, k.r, k.r * .66, 0, 0, 7); ctx.fill();
            ctx.fillStyle = '#e8b45f';
            ctx.beginPath(); ctx.ellipse(-1, k.r * .28, k.r * .68, k.r * .32, 0, 0, 7); ctx.fill();
            // cabeza con melena dentada
            var hx = -k.r - 2, hy = -4;
            ctx.fillStyle = ld;
            ctx.beginPath();
            for (var m = 0; m < 10; m++) {
                var ma = m / 10 * 6.283;
                var mr = k.r * .62 + (m % 2 ? 6.5 : 3);
                ctx.lineTo(hx + Math.cos(ma) * mr, hy + Math.sin(ma) * mr);
            }
            ctx.closePath(); ctx.fill();
            ctx.beginPath(); ctx.arc(hx - 4, hy - 6.5, 2.4, 0, 7); ctx.fill(); // oreja
            ctx.fillStyle = lb;
            ctx.beginPath(); ctx.arc(hx, hy, k.r * .62, 0, 7); ctx.fill();
            ctx.fillStyle = '#e8b45f';                                  // hocico
            ctx.beginPath(); ctx.ellipse(hx - k.r * .5, hy + 2, 4.2, 3.2, 0, 0, 7); ctx.fill();
            ctx.fillStyle = '#2b1c12';
            ctx.beginPath(); ctx.arc(hx - k.r * .5 - 2.5, hy + 1, 1.4, 0, 7); ctx.fill(); // nariz
            ctx.beginPath(); ctx.arc(hx - 1, hy - 2.5, 1.4, 0, 7); ctx.fill();            // ojo
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
        ctx.save(); ctx.translate(0, 21); shadow(12); ctx.restore();
        var aim = armT > 0 ? -Math.sin(armT * 10) * .8 : 0;
        // piernas + sandalias
        ctx.strokeStyle = '#d7a06a'; ctx.lineWidth = 3.6; ctx.lineCap = 'round';
        ctx.beginPath(); ctx.moveTo(-3, 10); ctx.lineTo(-3, 20); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(3, 10); ctx.lineTo(3, 20); ctx.stroke();
        ctx.fillStyle = '#6d4c41';
        ctx.fillRect(-5.4, 19, 5, 2.6); ctx.fillRect(1, 19, 5, 2.6);
        // túnica de pastor con orla y ceñidor
        ctx.fillStyle = '#e8dcc0';
        ctx.beginPath();
        ctx.moveTo(-7, 12); ctx.lineTo(-5, -6); ctx.lineTo(5, -6); ctx.lineTo(7, 12);
        ctx.closePath(); ctx.fill();
        ctx.fillStyle = '#8d6e63';
        ctx.fillRect(-7, 9.5, 14, 2.5);                                   // orla
        ctx.fillStyle = '#a03030';
        ctx.fillRect(-5, -2, 10, 2.4);                                    // ceñidor
        ctx.fillStyle = '#795548';                                        // zurrón de piedras
        ctx.beginPath(); ctx.arc(-6, 6, 3.2, 0, 7); ctx.fill();
        // cabeza: cabello con bucle + cinta azul
        ctx.fillStyle = '#d7a06a';
        ctx.beginPath(); ctx.arc(0, -13, 6, 0, 7); ctx.fill();
        ctx.fillStyle = '#4a2e1a';
        ctx.beginPath(); ctx.arc(0, -15.5, 6.4, Math.PI * 1.02, Math.PI * 1.98); ctx.fill();
        ctx.beginPath(); ctx.arc(-4, -13.5, 2.4, 0, 7); ctx.fill();
        ctx.fillStyle = '#3a5fb0';
        ctx.fillRect(-6.2, -16.4, 12.4, 2);
        ctx.fillStyle = '#2b1c12';
        ctx.beginPath(); ctx.arc(2.6, -13, 1.1, 0, 7); ctx.fill();        // ojo
        // brazo con honda: las cuerdas giran de verdad al cargar
        ctx.save(); ctx.translate(4, -8); ctx.rotate(-.7 + aim);
        ctx.strokeStyle = '#d7a06a'; ctx.lineWidth = 3.4;
        ctx.beginPath(); ctx.moveTo(0, 0); ctx.lineTo(12, 0); ctx.stroke();
        var sw = t * (armT > 0 ? .03 : .008);
        var lx = 12 + Math.cos(sw) * 8, ly = -3 + Math.sin(sw) * 5;
        ctx.strokeStyle = '#5d4037'; ctx.lineWidth = 1.6;
        ctx.beginPath();
        ctx.moveTo(12, 0); ctx.lineTo(lx, ly - 3.5);
        ctx.moveTo(12, 0); ctx.lineTo(lx, ly + 3.5);
        ctx.stroke();
        ctx.fillStyle = '#8d8d94';
        ctx.beginPath(); ctx.arc(lx, ly, 2.4, 0, 7); ctx.fill();          // piedra en la honda
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
        // .pn-menu lleva display:flex en CSS — el atributo `hidden` no le gana,
        // hay que ocultarlo con estilo inline (como hace la paloma)
        menu.style.display = 'none';
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
