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

    // Extremidad orgánica: trazo grueso con punta redonda (no fillRect)
    function limb(x1, y1, x2, y2, w, c) {
        ctx.strokeStyle = c; ctx.lineWidth = w; ctx.lineCap = 'round';
        ctx.beginPath(); ctx.moveTo(x1, y1); ctx.lineTo(x2, y2); ctx.stroke();
    }
    // Masa con volumen: gradiente radial, highlight arriba-izquierda
    function blob(cx, cy, rx, ry, c1, c2) {
        var g = ctx.createRadialGradient(cx - rx * .3, cy - ry * .35, rx * .2, cx, cy, rx * 1.15);
        g.addColorStop(0, c1); g.addColorStop(1, c2);
        ctx.fillStyle = g;
        ctx.beginPath(); ctx.ellipse(cx, cy, rx, ry, 0, 0, 7); ctx.fill();
    }

    function drawSheep(s) {
        ctx.save(); ctx.translate(s.x, s.y);
        ctx.save(); ctx.translate(0, 10); shadow(11); ctx.restore();
        var hop = Math.abs(Math.sin(s.ph * 4)) * 1.6;
        var w = Math.sin(s.ph * 4);                       // paso alterno
        limb(-5, 4, -5 + w * 1.6, 9.8, 2.8, '#5d4730');   // patitas curvas
        limb(0, 4.6, -w * 1.6, 10.2, 2.6, '#6b5340');
        limb(5, 4, 5 + w * 1.6, 9.8, 2.8, '#5d4730');
        ctx.translate(0, -hop);
        // lana: puffs con volumen (luz desde arriba-izquierda)
        for (var i = 0; i < 8; i++) {
            var a = i / 8 * 6.283;
            blob(Math.cos(a) * 6.6, Math.sin(a) * 4.4 - 1, 4.1, 4.1, '#ffffff', '#ddd8cc');
        }
        blob(0, -1, 6.8, 6.8, '#ffffff', '#e9e4d6');
        blob(-9.5, -1, 2.6, 2.6, '#ffffff', '#e9e4d6');   // cola
        // cabeza: gota curva con oreja caída
        ctx.fillStyle = '#4a3a28';
        ctx.beginPath();
        ctx.moveTo(7, -5);
        ctx.quadraticCurveTo(15, -7, 15.5, -2);
        ctx.quadraticCurveTo(15.5, 2.5, 10, 2);
        ctx.quadraticCurveTo(7, 0, 7, -5);
        ctx.fill();
        ctx.beginPath(); ctx.ellipse(8.5, -6, 3.4, 1.7, -.6, 0, 7); ctx.fill(); // oreja
        ctx.fillStyle = '#fff';
        ctx.beginPath(); ctx.arc(12.5, -3.4, 1.2, 0, 7); ctx.fill();
        ctx.fillStyle = '#2b1c12';
        ctx.beginPath(); ctx.arc(12.7, -3.3, .65, 0, 7); ctx.fill();
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
            ctx.save(); ctx.translate(0, 22); shadow(21); ctx.restore();
            // piernas: trazos curvos con pantorrilla + pies de sandalia
            ctx.strokeStyle = '#c98f5e'; ctx.lineWidth = 6; ctx.lineCap = 'round';
            ctx.beginPath(); ctx.moveTo(-5, 2); ctx.quadraticCurveTo(-8 + gg, 9, -8 + gg, 17); ctx.stroke();
            ctx.beginPath(); ctx.moveTo(5, 2); ctx.quadraticCurveTo(9 - gg, 9, 9 - gg, 17); ctx.stroke();
            ctx.strokeStyle = '#b8860b'; ctx.lineWidth = 6.4;           // grebas
            ctx.beginPath(); ctx.moveTo(-8 + gg, 12); ctx.lineTo(-8 + gg, 17); ctx.stroke();
            ctx.beginPath(); ctx.moveTo(9 - gg, 12); ctx.lineTo(9 - gg, 17); ctx.stroke();
            ctx.fillStyle = '#4e342e';                                  // sandalias
            ctx.beginPath(); ctx.ellipse(-9.5 + gg, 18, 4.8, 2, 0, 0, 7); ctx.fill();
            ctx.beginPath(); ctx.ellipse(10.5 - gg, 18, 4.8, 2, 0, 0, 7); ctx.fill();
            // faldellín: faldón curvo con tiras
            ctx.fillStyle = '#6b3a2a';
            ctx.beginPath();
            ctx.moveTo(-9, 0);
            ctx.quadraticCurveTo(-11, 6, -10, 11);
            ctx.quadraticCurveTo(0, 13, 10, 11);
            ctx.quadraticCurveTo(11, 6, 9, 0);
            ctx.closePath(); ctx.fill();
            ctx.strokeStyle = '#4e2a1e'; ctx.lineWidth = 1.4;
            ctx.beginPath(); ctx.moveTo(-4, 4); ctx.lineTo(-5, 11); ctx.stroke();
            ctx.beginPath(); ctx.moveTo(4, 4); ctx.lineTo(5, 11); ctx.stroke();
            // torso: coraza como óvalo muscular con bandas arqueadas
            blob(0, -8, 11.5, 13, '#d9b44a', '#9a7420');
            ctx.strokeStyle = 'rgba(96,64,16,.5)'; ctx.lineWidth = 1.7;
            for (var ry = -16; ry <= 0; ry += 4) {
                var bw = Math.sqrt(Math.max(0, 1 - Math.pow(ry / 13, 2))) * 11;
                ctx.beginPath();
                ctx.moveTo(-bw, ry); ctx.quadraticCurveTo(0, ry + 2.2, bw, ry);
                ctx.stroke();
            }
            // hombreras
            blob(-9, -17, 4.4, 3.4, '#d9b44a', '#9a7420');
            blob(9, -17, 4.4, 3.4, '#d9b44a', '#9a7420');
            // lanza al hombro: asta curva + punta de hoja
            ctx.strokeStyle = '#795548'; ctx.lineWidth = 2.8; ctx.lineCap = 'round';
            ctx.beginPath(); ctx.moveTo(14, 8 + gg); ctx.quadraticCurveTo(15, -18, 13, -37 + gg); ctx.stroke();
            ctx.fillStyle = '#9aa0a8';
            ctx.beginPath();
            ctx.moveTo(13, -45 + gg);
            ctx.quadraticCurveTo(17, -37 + gg, 15.5, -33 + gg);
            ctx.quadraticCurveTo(13, -31 + gg, 10.5, -33 + gg);
            ctx.quadraticCurveTo(9, -37 + gg, 13, -45 + gg);
            ctx.fill();
            // brazo sujetándola
            ctx.strokeStyle = '#c98f5e'; ctx.lineWidth = 5; ctx.lineCap = 'round';
            ctx.beginPath(); ctx.moveTo(8, -14); ctx.quadraticCurveTo(12, -10, 14, -7 + gg * .5); ctx.stroke();
            // cabeza: rostro volumétrico + barba + ceño
            blob(0, -24, 6.8, 6.8, '#e2b184', '#b5794a');
            ctx.fillStyle = '#4e342e';                                   // barba
            ctx.beginPath();
            ctx.moveTo(-5.5, -23);
            ctx.quadraticCurveTo(-6, -17, 0, -15.5);
            ctx.quadraticCurveTo(6, -17, 5.5, -23);
            ctx.quadraticCurveTo(0, -19, -5.5, -23);
            ctx.fill();
            ctx.fillStyle = '#2b1c12';
            ctx.beginPath(); ctx.arc(-3, -25.5, 1.2, 0, 7); ctx.fill();  // ojo
            ctx.strokeStyle = '#2b1c12'; ctx.lineWidth = 1.6; ctx.lineCap = 'round';
            ctx.beginPath(); ctx.moveTo(-6, -28.5); ctx.quadraticCurveTo(-3.5, -26.8, -1, -27); ctx.stroke(); // ceño
            // casco: media esfera suave + nasal + plumaje ondeando
            ctx.fillStyle = '#c9a227';
            ctx.beginPath(); ctx.arc(0, -27, 7.6, Math.PI, 0); ctx.fill();
            ctx.beginPath(); ctx.ellipse(0, -27, 7.9, 1.8, 0, 0, 7); ctx.fill();
            ctx.beginPath(); ctx.ellipse(-1.5, -24.5, 2.2, 3, 0, 0, 7); ctx.fill(); // nasal
            ctx.fillStyle = '#a03030';                                   // pluma
            ctx.beginPath();
            ctx.moveTo(-2, -33);
            ctx.quadraticCurveTo(4 + gg, -40, 14, -36 - Math.abs(gg));
            ctx.quadraticCurveTo(7, -32, -2, -33);
            ctx.fill();
            // escudo: círculo con volumen + umbo
            var sg = ctx.createRadialGradient(-16, -8, 3, -13, -4, 13);
            sg.addColorStop(0, '#a08070'); sg.addColorStop(1, '#5d4037');
            ctx.fillStyle = sg;
            ctx.beginPath(); ctx.arc(-13, -4, 11, 0, 7); ctx.fill();
            ctx.strokeStyle = '#c9a227'; ctx.lineWidth = 2.6;
            ctx.beginPath(); ctx.arc(-13, -4, 11, 0, 7); ctx.stroke();
            ctx.beginPath(); ctx.arc(-13, -4, 5.5, 0, 7); ctx.stroke();
            ctx.fillStyle = '#c9a227';
            ctx.beginPath(); ctx.arc(-13, -4, 3.2, 0, 7); ctx.fill();
        } else if (f.kind === 'bear') {
            var bg = Math.sin(f.ph * 7);
            ctx.save(); ctx.translate(0, k.r + 9); shadow(k.r + 4); ctx.restore();
            // patas: tronquitos redondeados en marcha alterna
            limb(-k.r + 4, k.r * .3, -k.r + 2 + bg * 2.4, k.r * 1.35, 6, '#523619');
            limb(k.r - 8, k.r * .3, k.r - 6 - bg * 2.4, k.r * 1.35, 6, '#523619');
            limb(-k.r + 10, k.r * .4, -k.r + 9 - bg * 2, k.r * 1.3, 5.6, '#63432a');
            limb(k.r - 15, k.r * .4, k.r - 14 + bg * 2, k.r * 1.3, 5.6, '#63432a');
            // cuerpo: pera con gradiente + giba fundida
            blob(0, 0, k.r, k.r * .78, '#8a6238', '#5c3d20');
            blob(-k.r * .3, -k.r * .52, k.r * .55, k.r * .42, '#8a6238', '#6b4a28');
            // panza clara curva
            ctx.fillStyle = 'rgba(180,140,100,.5)';
            ctx.beginPath(); ctx.ellipse(-2, k.r * .32, k.r * .55, k.r * .28, 0, 0, 7); ctx.fill();
            blob(k.r * .85, -2, 2.8, 2.8, '#8a6238', '#5c3d20');         // rabito
            // cabeza: volumen + orejas + hocico claro + nariz húmeda
            var bhx = -k.r - 2, bhy = -6;
            blob(bhx, bhy, k.r * .6, k.r * .58, '#8a6238', '#5c3d20');
            blob(bhx - 3.5, bhy - 8, 3.6, 3.6, '#7a5230', '#523619');
            blob(bhx + 5, bhy - 8.4, 3.6, 3.6, '#7a5230', '#523619');
            ctx.fillStyle = '#3e2723';
            ctx.beginPath(); ctx.arc(bhx - 3.5, bhy - 8, 1.8, 0, 7); ctx.fill();
            ctx.beginPath(); ctx.arc(bhx + 5, bhy - 8.4, 1.8, 0, 7); ctx.fill();
            ctx.fillStyle = '#b09479';                                   // hocico
            ctx.beginPath();
            ctx.moveTo(bhx - 12.5, bhy + 1);
            ctx.quadraticCurveTo(bhx - 12.5, bhy - 3, bhx - 8, bhy - 2.5);
            ctx.quadraticCurveTo(bhx - 3, bhy - 2, bhx - 3.5, bhy + 2);
            ctx.quadraticCurveTo(bhx - 5, bhy + 5.5, bhx - 9, bhy + 5);
            ctx.quadraticCurveTo(bhx - 12.5, bhy + 5, bhx - 12.5, bhy + 1);
            ctx.fill();
            ctx.fillStyle = '#2b1c12';
            ctx.beginPath(); ctx.ellipse(bhx - 11.8, bhy, 2.1, 1.8, 0, 0, 7); ctx.fill(); // nariz
            ctx.beginPath(); ctx.arc(bhx - 1.5, bhy - 3.5, 1.5, 0, 7); ctx.fill();        // ojo
        } else {
            var lg = Math.sin(f.ph * 9);
            ctx.save(); ctx.translate(0, k.r + 9); shadow(k.r + 4); ctx.restore();
            // cola: curva viva con mechón
            ctx.strokeStyle = '#c07f28'; ctx.lineWidth = 2.6; ctx.lineCap = 'round';
            ctx.beginPath(); ctx.moveTo(k.r - 2, -4);
            ctx.quadraticCurveTo(k.r + 10, -8 - lg * 3, k.r + 5, -16 - lg);
            ctx.stroke();
            blob(k.r + 5, -16 - lg, 3.2, 3.2, '#8f5410', '#6b3d0a');
            // patas esbeltas en marcha
            limb(-k.r + 5, k.r * .45, -k.r + 3 + lg * 2.4, k.r * 1.35, 4.4, '#b26e1e');
            limb(k.r - 9, k.r * .45, k.r - 7 - lg * 2.4, k.r * 1.35, 4.4, '#b26e1e');
            limb(-k.r + 11, k.r * .5, -k.r + 10 - lg * 2, k.r * 1.3, 3.8, '#c07f28');
            // cuerpo felino con gradiente
            blob(-1, 0, k.r, k.r * .68, '#e8aa4e', '#b06e16');
            ctx.fillStyle = 'rgba(240,200,120,.6)';                      // panza
            ctx.beginPath(); ctx.ellipse(-1, k.r * .3, k.r * .6, k.r * .3, 0, 0, 7); ctx.fill();
            // melena: anillo de mechones solapados (peludo, no dentado)
            var hx = -k.r - 2, hy = -4;
            for (var m = 0; m < 10; m++) {
                var ma = m / 10 * 6.283;
                blob(hx + Math.cos(ma) * (k.r * .58), hy + Math.sin(ma) * (k.r * .58),
                    k.r * .34, k.r * .34, '#9a5c12', '#6e3f08');
            }
            blob(hx, hy, k.r * .6, k.r * .6, '#e8aa4e', '#b06e16');      // cara
            blob(hx - 2, hy - k.r * .55, 2.8, 2.8, '#c07f28', '#8f5410'); // oreja
            // hocico + nariz + ojo
            ctx.fillStyle = '#f0c87e';
            ctx.beginPath();
            ctx.moveTo(hx - k.r * .5 - 4.4, hy + 1);
            ctx.quadraticCurveTo(hx - k.r * .5 - 4.4, hy - 1.5, hx - k.r * .5 - 1, hy - 1.5);
            ctx.quadraticCurveTo(hx - k.r * .5 + 3, hy - 1.5, hx - k.r * .5 + 3, hy + 2);
            ctx.quadraticCurveTo(hx - k.r * .5 + 1, hy + 5, hx - k.r * .5 - 2, hy + 5);
            ctx.quadraticCurveTo(hx - k.r * .5 - 4.4, hy + 4.5, hx - k.r * .5 - 4.4, hy + 1);
            ctx.fill();
            ctx.fillStyle = '#4a2e1a';
            ctx.beginPath(); ctx.ellipse(hx - k.r * .5 - 3, hy - .5, 1.8, 1.4, 0, 0, 7); ctx.fill(); // nariz
            ctx.fillStyle = '#2b1c12';
            ctx.beginPath(); ctx.arc(hx - 1, hy - 3, 1.5, 0, 7); ctx.fill();                        // ojo
        }
        // vida: píldora redondeada
        ctx.globalAlpha = 1;
        ctx.lineCap = 'round';
        ctx.strokeStyle = 'rgba(0,0,0,.3)'; ctx.lineWidth = 5;
        ctx.beginPath(); ctx.moveTo(-k.r + 2, -k.r - 14); ctx.lineTo(k.r - 2, -k.r - 14); ctx.stroke();
        if (f.hp > 0) {
            ctx.strokeStyle = f.hp > k.hp / 2 ? '#6fbf5a' : '#e05252'; ctx.lineWidth = 3.4;
            ctx.beginPath();
            ctx.moveTo(-k.r + 2, -k.r - 14);
            ctx.lineTo(-k.r + 2 + (k.r * 2 - 4) * (f.hp / k.hp), -k.r - 14);
            ctx.stroke();
        }
        ctx.restore();
    }

    function drawDavid() {
        ctx.save(); ctx.translate(84, 178);
        ctx.save(); ctx.translate(0, 21); shadow(12); ctx.restore();
        var aim = armT > 0 ? -Math.sin(armT * 10) * .8 : 0;
        // piernas curvas + pies de sandalia
        limb(-2.5, 8, -3.5, 18, 3.8, '#c98f5e');
        limb(2.5, 8, 3.5, 18, 3.8, '#c98f5e');
        ctx.fillStyle = '#6d4c41';
        ctx.beginPath(); ctx.ellipse(-4.5, 19.5, 3.4, 1.7, 0, 0, 7); ctx.fill();
        ctx.beginPath(); ctx.ellipse(4.5, 19.5, 3.4, 1.7, 0, 0, 7); ctx.fill();
        // túnica: campana curva de hombro a bajo, no trapecio
        ctx.fillStyle = '#e8dcc0';
        ctx.beginPath();
        ctx.moveTo(-5.5, -6);
        ctx.quadraticCurveTo(-8, 0, -8.5, 9);
        ctx.quadraticCurveTo(-8.5, 13, -3, 13);
        ctx.quadraticCurveTo(0, 13.5, 3, 13);
        ctx.quadraticCurveTo(8.5, 13, 8.5, 9);
        ctx.quadraticCurveTo(8, 0, 5.5, -6);
        ctx.quadraticCurveTo(0, -9, -5.5, -6);
        ctx.fill();
        // orla y ceñidor como trazos que siguen la tela
        ctx.strokeStyle = '#8d6e63'; ctx.lineWidth = 2; ctx.lineCap = 'round';
        ctx.beginPath(); ctx.moveTo(-7.8, 9.5); ctx.quadraticCurveTo(0, 12.4, 7.8, 9.5); ctx.stroke();
        ctx.strokeStyle = '#a03030'; ctx.lineWidth = 2.6;
        ctx.beginPath(); ctx.moveTo(-5.6, -1); ctx.quadraticCurveTo(0, 1.2, 5.6, -1); ctx.stroke();
        // zurrón: bolsita redonda con nudo
        blob(-6.5, 5.5, 3.4, 3.6, '#9a7355', '#6b4a30');
        ctx.fillStyle = '#5d4037';
        ctx.beginPath(); ctx.arc(-6.5, 2.2, 1.2, 0, 7); ctx.fill();
        // cabeza con volumen
        blob(0, -13, 6.2, 6.2, '#eec19a', '#c98f5e');
        // cabello: copete de rizos + bucle lateral
        ctx.fillStyle = '#4a2e1a';
        ctx.beginPath(); ctx.arc(0, -16, 6, Math.PI * .98, Math.PI * 2); ctx.fill();
        ctx.beginPath(); ctx.arc(-4.2, -16.5, 2.6, 0, 7); ctx.fill();
        ctx.beginPath(); ctx.arc(0.5, -18.5, 2.8, 0, 7); ctx.fill();
        ctx.beginPath(); ctx.arc(4, -16.5, 2.4, 0, 7); ctx.fill();
        ctx.beginPath(); ctx.arc(-5, -13, 2.2, 0, 7); ctx.fill();        // bucle
        // cinta azul siguiendo la curva del cabello
        ctx.strokeStyle = '#3a5fb0'; ctx.lineWidth = 2; ctx.lineCap = 'round';
        ctx.beginPath(); ctx.arc(0, -14.5, 5.9, Math.PI * 1.08, Math.PI * 1.92); ctx.stroke();
        ctx.fillStyle = '#2b1c12';
        ctx.beginPath(); ctx.arc(2.4, -12.6, 1.1, 0, 7); ctx.fill();     // ojo
        ctx.strokeStyle = '#b5794a'; ctx.lineWidth = 1.2;
        ctx.beginPath(); ctx.arc(3.5, -10, 1.6, .3, Math.PI * .8); ctx.stroke(); // sonrisa
        // brazo con honda: las cuerdas giran de verdad al cargar
        ctx.save(); ctx.translate(4, -8); ctx.rotate(-.7 + aim);
        limb(0, 0, 12, 0, 3.6, '#d7a06a');
        var sw = t * (armT > 0 ? .03 : .008);
        var lx = 12 + Math.cos(sw) * 8, ly = -3 + Math.sin(sw) * 5;
        ctx.strokeStyle = '#5d4037'; ctx.lineWidth = 1.6; ctx.lineCap = 'round';
        ctx.beginPath();
        ctx.moveTo(12, 0); ctx.lineTo(lx, ly - 3.5);
        ctx.moveTo(12, 0); ctx.lineTo(lx, ly + 3.5);
        ctx.stroke();
        blob(lx, ly, 2.6, 2.6, '#a8a8b0', '#70707a');                    // piedra
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
