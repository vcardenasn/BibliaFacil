// La Paloma de Noé — runner estilo dino/flappy con progresión narrativa.
// Tres vuelos basados en Génesis 8:8-12: la paloma sale del arca, vuelve con
// la rama de olivo y por fin encuentra tierra en el monte Ararat (arcoíris).
// Tras el 3er vuelo queda el modo libre sin fin. Canvas 2D a devicePixelRatio
// (nítido en retina) y controles con fallback touchstart/keyCode (iOS <13).
BFJ.define('paloma', function (el) {
    // Vuelos = niveles de la historia (Gén 8:8-12)
    var FLIGHTS = [
        {
            name: BFJ.T('Vuelo 1 · La primera salida'),
            ref: BFJ.T('Génesis') + ' 8:8-9',
            desc: BFJ.T('Noé soltó a la paloma para ver si había bajado el agua.'),
            goal: 250, gapAdd: 26, olivesNeeded: 0
        },
        {
            name: BFJ.T('Vuelo 2 · La rama de olivo'),
            ref: BFJ.T('Génesis') + ' 8:10-11',
            desc: BFJ.T('La paloma volvió al atardecer con una rama de olivo en el pico.'),
            goal: 380, gapAdd: 10, olivesNeeded: 2
        },
        {
            name: BFJ.T('Vuelo 3 · El Monte Ararat'),
            ref: BFJ.T('Génesis') + ' 8:12 · 9:13',
            desc: BFJ.T('Esta vez la paloma no regresó: ¡encontró tierra firme!'),
            goal: 520, gapAdd: 0, olivesNeeded: 3, ararat: true
        },
        {
            name: BFJ.T('Vuelo libre · Hasta el horizonte'),
            ref: BFJ.T('Génesis') + ' 9:13',
            desc: BFJ.T('La tierra floreció de nuevo. ¿Hasta dónde llegas?'),
            goal: 0, gapAdd: 0, olivesNeeded: 0, endless: true
        }
    ];

    var passed = BFJ.levels.passed('paloma'); // 0-3
    var fi = Math.min(passed, 3);
    var best = 0;
    try { best = parseInt(localStorage.getItem('bf_pal_best') || '0', 10) || 0; } catch (e) {}

    function menuHTML() {
        var f = FLIGHTS[fi];
        return (
            '<p class="pn-emo" aria-hidden="true">🕊️</p>' +
            '<p class="pn-t">' + BFJ.esc(f.name) + '</p>' +
            '<p class="pn-s">' + BFJ.esc(f.desc) + '</p>' +
            '<p class="pn-ref">📖 ' + f.ref + '</p>' +
            (f.endless
                ? '<p class="pn-goal">' + BFJ.T('🏁 Sin meta — ¡solo vuela!') + '</p>'
                : '<p class="pn-goal">🏁 ' + BFJ.T('Meta:') + ' ' + f.goal + ' m' +
                  (f.olivesNeeded ? ' + ' + f.olivesNeeded + ' ' + BFJ.T('ramas 🌿') : '') + '</p>') +
            (best ? '<p class="pn-best">✨ ' + BFJ.T('Tu récord:') + ' ' + best + ' m</p>' : '') +
            '<button type="button" class="jbtn jbtn-main" id="pnGo">' + BFJ.T('▶ ¡A volar!') + '</button>' +
            '<p class="pn-k">' + BFJ.T('👆 Toca la pantalla o presiona <kbd>espacio</kbd> para volar') + '</p>'
        );
    }

    el.innerHTML =
        '<div class="pn-stage" id="pnStage">' +
        '<canvas class="pn-cv" id="pnCv" width="480" height="320" ' +
        'aria-label="' + BFJ.T('La Paloma de Noé: esquiva las nubes y recoge ramas de olivo') + '"></canvas>' +
        '<div class="pn-prog" id="pnProg"><i id="pnFill"></i><b id="pnIco">🕊️</b></div>' +
        '<div class="pn-hud" aria-hidden="true">' +
        '<span id="pnOl">🌿 0</span><span id="pnM">0 m</span>' +
        '</div>' +
        '<div class="pn-hint" id="pnHint" hidden>' + BFJ.T('👆 ¡Toca para volar!') + '</div>' +
        '<div class="pn-menu" id="pnMenu"></div>' +
        '</div>';

    var cv = document.getElementById('pnCv');
    var ctx = cv.getContext('2d');
    var stage = document.getElementById('pnStage');
    var menu = document.getElementById('pnMenu');
    var hint = document.getElementById('pnHint');
    var prog = document.getElementById('pnProg');
    var pFill = document.getElementById('pnFill');
    var pIco = document.getElementById('pnIco');
    var hudOl = document.getElementById('pnOl');
    var hudM = document.getElementById('pnM');
    var W = 480, H = 320;
    var SEA = 34, SKY = 8;

    var DPR = Math.min(2, window.devicePixelRatio || 1);
    cv.width = W * DPR; cv.height = H * DPR;
    ctx.scale(DPR, DPR);

    var raf = null, flying = false, over = false, won = false;
    var dove, clouds, olives, parts, rain, bgs, popups;
    var t, dist, got, speed, spawnIn, seaX, flapT, ararat, rainbow;

    function flight() { return FLIGHTS[fi]; }

    function reset() {
        dove = { x: 90, y: H * .45, vy: 0, r: 13 };
        clouds = []; olives = []; parts = []; rain = []; popups = [];
        bgs = [
            { x: 60, y: 46, s: 1.0 }, { x: 230, y: 30, s: .7 },
            { x: 390, y: 60, s: 1.2 }, { x: 520, y: 38, s: .8 }
        ];
        t = 0; dist = 0; got = 0; speed = 120; spawnIn = 480; seaX = 0; flapT = 0;
        ararat = 0; rainbow = 0;
    }

    function flap() {
        if (over || won) { return; }
        if (!flying) {
            flying = true;
            hint.hidden = true;
        }
        dove.vy = -190;
        flapT = .55;
        BFJ.snd('flap');
    }

    function spawnCloud() {
        var f = flight();
        var gap = Math.max(84, H * .34 - t * .5 + f.gapAdd);
        var cy = 40 + Math.random() * (H - SEA - 80 - gap);
        clouds.push({ x: W + 40, gy: cy, gh: gap, w: 56, bolt: Math.random() < .5 });
        if (Math.random() < .75) {
            olives.push({
                x: W + 68, y: cy + gap / 2 + (Math.random() - .5) * gap * .4,
                taken: false, ph: Math.random() * 6.28
            });
        }
    }

    function hit(a, b) {
        var dx = a.x - b.x, dy = a.y - b.y;
        return dx * dx + dy * dy < (a.r + b.r) * (a.r + b.r);
    }

    function cloudHits(cx, gy, gh, cw) {
        var dx = dove.x, r = dove.r * .82;
        if (dx + r > cx && dx - r < cx + cw) {
            if (dove.y - r < gy || dove.y + r > gy + gh) { return true; }
        }
        return false;
    }

    function popup(x, y, txt) {
        popups.push({ x: x, y: y, txt: txt, life: 1 });
    }

    function saveBest() {
        var m = Math.floor(dist);
        if (m > best) {
            best = m;
            try { localStorage.setItem('bf_pal_best', String(m)); } catch (e) {}
            return true;
        }
        return false;
    }

    function die() {
        if (over || won) { return; }
        over = true;
        BFJ.snd('bad');
        BFJ.shake(stage);
        for (var i = 0; i < 14; i++) {
            parts.push({
                x: dove.x, y: dove.y,
                vx: (Math.random() - .5) * 170, vy: -Math.random() * 130,
                s: 2 + Math.random() * 3.4, life: 1
            });
        }
        setTimeout(function () { finish(false); }, 900);
    }

    function win() {
        if (won || over) { return; }
        won = true;
        BFJ.snd('ok');
        setTimeout(function () { finish(true); }, flight().ararat ? 1600 : 700);
    }

    function finish(success) {
        cancelAnimationFrame(raf);
        flying = false;
        var m = Math.floor(dist);
        var isRecord = saveBest();
        var f = flight();
        var stars = Math.min(10, got + Math.floor(m / 120) + (success ? 2 : 0));

        if (success && !f.endless) {
            BFJ.levels.pass('paloma', fi, m); // desbloquea el siguiente vuelo
        }

        var emoji, title;
        if (success && f.ararat) {
            emoji = '🌈'; title = BFJ.T('¡Encontró tierra firme!');
        } else if (success) {
            emoji = '🕊️'; title = BFJ.T('¡Vuelo completado!');
        } else {
            emoji = '⛈️'; title = m >= 150 ? BFJ.T('¡Buen intento!') : BFJ.T('¡Sigue intentando!');
        }
        var extra = BFJ.T('Volaste') + ' ' + m + ' m' +
            (got ? ' · ' + got + ' ' + (got === 1 ? BFJ.T('rama') : BFJ.T('ramas')) + ' ' + BFJ.T('de olivo 🌿') : '') +
            (isRecord ? ' · ' + BFJ.T('¡Nuevo récord! ✨') : '');

        BFJ.celebrate({
            slug: 'paloma', stars: stars, emoji: emoji, title: title, extra: extra,
            againLabel: success && fi < 3 ? BFJ.T('🕊️ Siguiente vuelo') : BFJ.T('🔁 Otra vez'),
            onAgain: function () {
                if (success && fi < 3) { fi++; }
                showMenu();
            }
        });
    }

    function step(dt) {
        t += dt * 1000;
        flapT = Math.max(0, flapT - dt);
        var f = flight();

        // Antes del primer toque: la paloma se mantiene en el aire, mundo quieto
        if (!flying) {
            dove.y = H * .45 + Math.sin(t * .004) * 8;
            return;
        }

        // Fase final del vuelo 3: el Ararat sale del agua y aparece el arcoíris
        var nearingEnd = !f.endless && dist > f.goal - 140;
        if (f.ararat && nearingEnd) {
            ararat = Math.min(1, ararat + dt * .8);
            if (dist >= f.goal) { rainbow = Math.min(1, rainbow + dt * .6); }
        }

        speed = Math.min(300, 120 + t * .014);
        if (won) { speed = Math.max(40, speed - dt * 300); } // aterriza suave
        dist += speed * dt * .12;
        seaX -= speed * dt;

        // paloma
        dove.vy += 640 * dt;
        dove.y += dove.vy * dt;
        if (dove.y - dove.r < SKY) { dove.y = SKY + dove.r; dove.vy = 0; }
        if (dove.y + dove.r > H - SEA) { dove.y = H - SEA - dove.r; die(); }

        // nubes (dejan de salir al cumplir la meta)
        if (!won && !nearingEnd) {
            spawnIn -= speed * dt;
            if (spawnIn <= 0) { spawnCloud(); spawnIn = 260 + Math.random() * 120; }
        }
        for (var i = clouds.length - 1; i >= 0; i--) {
            clouds[i].x -= speed * dt;
            if (!over && !won && cloudHits(clouds[i].x, clouds[i].gy, clouds[i].gh, clouds[i].w)) { die(); }
            if (clouds[i].x < -80) { clouds.splice(i, 1); }
        }

        // Si la meta exige ramas y faltan, siguen apareciendo aunque ya no
        // salgan nubes — el jugador nunca queda atrapado sin poder ganar.
        if (!f.endless && dist >= f.goal - 140 && got < f.olivesNeeded && olives.length < 3) {
            if (Math.random() < .012) {
                olives.push({
                    x: W + 30, y: 60 + Math.random() * (H - SEA - 120),
                    taken: false, ph: Math.random() * 6.28
                });
            }
        }

        // ramas de olivo
        for (var j = olives.length - 1; j >= 0; j--) {
            var o = olives[j];
            o.x -= speed * dt;
            if (!o.taken && !over && hit(dove, { x: o.x, y: o.y, r: 11 })) {
                o.taken = true; got++;
                hudOl.textContent = '🌿 ' + got + (f.olivesNeeded ? '/' + f.olivesNeeded : '');
                popup(o.x, o.y - 12, '+1 🌿');
                BFJ.snd('sparkle');
                // meta de ramas cumplida + distancia cumplida → victoria
                if (!f.endless && got >= f.olivesNeeded && dist >= f.goal) { win(); }
            }
            if (o.x < -30) { olives.splice(j, 1); }
        }

        // victoria por distancia cuando no piden ramas, o meta ya conseguida
        if (!f.endless && !won && dist >= f.goal && got >= f.olivesNeeded) { win(); }

        // lluvia (más intensa en los últimos vuelos)
        if (rain.length < 30 + fi * 8 && Math.random() < .5) {
            rain.push({ x: Math.random() * (W + 40), y: -10 });
        }
        for (var r = rain.length - 1; r >= 0; r--) {
            rain[r].y += 340 * dt; rain[r].x -= 60 * dt;
            if (rain[r].y > H - SEA) { rain.splice(r, 1); }
        }

        // nubes lejanas (parallax)
        bgs.forEach(function (b) {
            b.x -= speed * dt * .18;
            if (b.x < -60) { b.x = W + 60; b.y = 24 + Math.random() * 50; }
        });

        // plumas y textos flotantes
        for (var k = parts.length - 1; k >= 0; k--) {
            var p = parts[k];
            p.x += p.vx * dt; p.y += p.vy * dt; p.vy += 300 * dt; p.life -= dt * 1.4;
            if (p.life <= 0) { parts.splice(k, 1); }
        }
        for (var u = popups.length - 1; u >= 0; u--) {
            popups[u].y -= 30 * dt; popups[u].life -= dt * 1.1;
            if (popups[u].life <= 0) { popups.splice(u, 1); }
        }

        hudM.textContent = Math.floor(dist) + ' m';
        if (!f.endless) {
            var pct = Math.min(100, dist / f.goal * 100);
            pFill.style.width = pct + '%';
            pIco.style.left = 'calc(' + pct + '% - 8px)';
        }
    }

    // ---------- Dibujo ----------

    function puff(x, y, r) {
        ctx.beginPath(); ctx.arc(x, y, r, 0, 7); ctx.fill();
    }

    function drawCloudCol(x, y, w, h, top, bolt) {
        ctx.fillStyle = '#e9eff6';
        ctx.strokeStyle = 'rgba(110,130,160,.55)';
        ctx.lineWidth = 2;
        var n = Math.max(2, Math.round(h / 24));
        for (var i = 0; i < n; i++) {
            var cy = top ? (y + h - 13 - i * 23) : (y + 13 + i * 23);
            puff(x + w * .26, cy, 16);
            puff(x + w * .60, cy + (i % 2 ? 4 : -3), 19);
            puff(x + w * .90, cy, 13);
            ctx.stroke();
        }
        if (top && bolt && h > 60) {
            ctx.fillStyle = '#f7c948';
            ctx.strokeStyle = 'rgba(160,110,0,.4)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            var bx = x + w * .5, by = y + h - 4;
            ctx.moveTo(bx + 4, by); ctx.lineTo(bx - 4, by + 15); ctx.lineTo(bx + 1, by + 15);
            ctx.lineTo(bx - 6, by + 30); ctx.lineTo(bx + 6, by + 12); ctx.lineTo(bx, by + 12);
            ctx.closePath(); ctx.fill(); ctx.stroke();
        }
    }

    function drawOlive(x, y, ph) {
        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(Math.sin(t * .005 + ph) * .25 - .4);
        ctx.strokeStyle = '#5b8c3e'; ctx.lineWidth = 2; ctx.lineCap = 'round';
        ctx.beginPath(); ctx.moveTo(-9, 6); ctx.quadraticCurveTo(0, -2, 9, -7); ctx.stroke();
        ctx.fillStyle = '#6aa84f';
        [[-4, 1], [1, -3], [6, -6]].forEach(function (l, i) {
            ctx.save();
            ctx.translate(l[0], l[1]); ctx.rotate(-.7 - i * .25);
            ctx.scale(1, .45);
            ctx.beginPath(); ctx.arc(0, 0, 5, 0, 7); ctx.fill();
            ctx.restore();
        });
        ctx.restore();
    }

    function drawArarat() {
        // el monte emerge del agua por la derecha
        var mh = 90 * ararat, mx = W - 90;
        if (mh <= 0) { return; }
        ctx.fillStyle = '#8a9bb0';
        ctx.beginPath();
        ctx.moveTo(mx - 30, H - SEA); ctx.lineTo(mx + 20, H - SEA - mh);
        ctx.lineTo(mx + 55, H - SEA - mh * .72); ctx.lineTo(mx + 100, H - SEA);
        ctx.closePath(); ctx.fill();
        // nieve
        ctx.fillStyle = '#f4f8fb';
        ctx.beginPath();
        ctx.moveTo(mx + 8, H - SEA - mh * .82); ctx.lineTo(mx + 20, H - SEA - mh);
        ctx.lineTo(mx + 36, H - SEA - mh * .8); ctx.closePath(); ctx.fill();
        // vegetación baja
        ctx.fillStyle = '#5d8a4a';
        ctx.beginPath();
        ctx.moveTo(mx - 20, H - SEA); ctx.lineTo(mx + 15, H - SEA - mh * .35);
        ctx.lineTo(mx + 70, H - SEA); ctx.closePath(); ctx.fill();
    }

    function drawRainbow() {
        if (rainbow <= 0) { return; }
        var cx = W * .62, cy = H - SEA;
        var cols = ['#e8574d', '#f2a03d', '#f7d154', '#5cb85c', '#4d9de0', '#7b6fd0'];
        ctx.lineWidth = 5;
        ctx.globalAlpha = rainbow * .85;
        cols.forEach(function (c, i) {
            ctx.strokeStyle = c;
            ctx.beginPath();
            ctx.arc(cx, cy, 78 - i * 5.2, Math.PI, 2 * Math.PI);
            ctx.stroke();
        });
        ctx.globalAlpha = 1;
    }

    // ==== Sprite de la paloma: atlas prerenderizado con frames de aleteo ====
    // El arte vectorial se pinta UNA vez en 6 frames (alas arriba → abajo) a
    // devicePixelRatio; en runtime solo se hace drawImage — más nítido, más
    // barato por frame y animación de frames como un sprite-sheet real.
    var FW = 64, FH = 56;                    // tamaño lógico del frame
    var WINGS = [-1.15, -.72, -.3, .12, .55, .95]; // poses de ala
    var SPR = [];

    // Ala con plumas: borde de ataque curvo, 3 puntas en el borde de fuga
    function wingShape(g, dark) {
        g.fillStyle = dark ? '#c9d4e0' : '#f2f6fa';
        g.strokeStyle = '#a8b8c8'; g.lineWidth = 1;
        g.beginPath();
        g.moveTo(0, 0);
        g.quadraticCurveTo(-6, -9, -16, -12);       // borde de ataque
        g.quadraticCurveTo(-21, -13, -23, -10);     // punta
        g.lineTo(-15, -7);                           // pluma 1
        g.lineTo(-19, -3); g.lineTo(-11, -3);        // pluma 2
        g.lineTo(-13, 1); g.lineTo(-5, 0);           // pluma 3
        g.quadraticCurveTo(-1, 0, 0, 0);
        g.closePath(); g.fill(); g.stroke();
    }

    function paintDove(g, wing) {
        // cola en abanico
        g.fillStyle = '#dde5ec';
        g.beginPath();
        g.moveTo(-9, -1); g.lineTo(-24, -8); g.lineTo(-21, -2);
        g.lineTo(-24, 4); g.lineTo(-9, 3);
        g.closePath(); g.fill();

        // ala trasera (detrás del cuerpo, se mueve con desfase)
        g.save(); g.translate(-2, -3); g.rotate(-wing * .8 - .15);
        wingShape(g, true);
        g.restore();

        // cuerpo con gradiente
        g.save(); g.scale(1.35, .95);
        var bg = g.createRadialGradient(-2, -2, 2, 0, 0, 15);
        bg.addColorStop(0, '#ffffff'); bg.addColorStop(1, '#e2e9f0');
        g.fillStyle = bg;
        g.beginPath(); g.arc(0, 0, 11, 0, 7); g.fill();
        g.restore();

        // ala delantera emplumada
        g.save(); g.translate(-1, -4); g.rotate(-wing);
        wingShape(g, false);
        g.restore();

        // cabeza + pico + ojo
        g.fillStyle = '#f6f9fc';
        g.beginPath(); g.arc(10, -7, 6.5, 0, 7); g.fill();
        g.fillStyle = '#f0a030';
        g.beginPath(); g.moveTo(15, -8); g.lineTo(22, -6); g.lineTo(15, -5);
        g.closePath(); g.fill();
        g.fillStyle = '#2b3a4a';
        g.beginPath(); g.arc(11.5, -8.5, 1.4, 0, 7); g.fill();
    }

    WINGS.forEach(function (w) {
        var fc = document.createElement('canvas');
        fc.width = FW * DPR; fc.height = FH * DPR;
        var g = fc.getContext('2d');
        g.scale(DPR, DPR);
        g.translate(FW / 2, FH / 2 + 2);
        paintDove(g, w);
        SPR.push(fc);
    });

    function drawDove() {
        var bob = Math.sin(t * .006) * 2;
        var rot = Math.max(-.5, Math.min(.7, dove.vy / 320));
        var wSpd = flapT > 0 ? .09 : .02;
        var wing = Math.sin(t * wSpd) * (flapT > 0 ? .95 : .3) - .25;
        var si = Math.round((wing - WINGS[0]) / (WINGS[5] - WINGS[0]) * (SPR.length - 1));
        si = Math.max(0, Math.min(SPR.length - 1, si));

        ctx.save();
        ctx.translate(dove.x, dove.y + bob * .3);
        ctx.rotate(rot);

        // estela: la paloma deja aire al moverse rápido
        if (speed > 200) {
            ctx.strokeStyle = 'rgba(255,255,255,.35)';
            ctx.lineWidth = 1.5;
            for (var s = 0; s < 3; s++) {
                ctx.beginPath();
                ctx.moveTo(-20 - s * 8, -4 + s * 5);
                ctx.lineTo(-34 - s * 10, -4 + s * 5);
                ctx.stroke();
            }
        }

        ctx.drawImage(SPR[si], -FW / 2, -FH / 2 - 2, FW, FH);

        // ramita de olivo en el pico si ya recogió alguna
        if (got > 0) {
            ctx.strokeStyle = '#5b8c3e'; ctx.lineWidth = 1.6;
            ctx.beginPath(); ctx.moveTo(20, -5); ctx.quadraticCurveTo(26, -2, 29, 2); ctx.stroke();
            ctx.fillStyle = '#6aa84f';
            ctx.beginPath(); ctx.arc(28, 0, 2.6, 0, 7); ctx.fill();
            ctx.beginPath(); ctx.arc(30, 3, 2.6, 0, 7); ctx.fill();
        }
        ctx.restore();
    }

    function draw() {
        // cielo (se aclara al final del vuelo 3)
        var dawn = rainbow > 0 ? rainbow : ararat * .4;
        var sky = ctx.createLinearGradient(0, 0, 0, H);
        sky.addColorStop(0, dawn ? '#8fbfe8' : '#7fb2e0');
        sky.addColorStop(.65, '#b9d6f0');
        sky.addColorStop(1, dawn ? '#f3e8c8' : '#dcecf9');
        ctx.fillStyle = sky; ctx.fillRect(0, 0, W, H);
        ctx.fillStyle = 'rgba(247,201,72,.28)';
        ctx.beginPath(); ctx.arc(W - 58, 42, 36, 0, 7); ctx.fill();
        ctx.fillStyle = '#f7c948';
        ctx.beginPath(); ctx.arc(W - 58, 42, 22, 0, 7); ctx.fill();

        // nubes lejanas
        ctx.fillStyle = 'rgba(255,255,255,.5)';
        bgs.forEach(function (b) {
            ctx.save(); ctx.translate(b.x, b.y); ctx.scale(b.s, b.s * .8);
            puff(0, 0, 14); puff(16, 2, 11); puff(-15, 3, 10);
            ctx.restore();
        });

        drawRainbow();
        drawArarat();

        // nubes obstáculo
        clouds.forEach(function (c) {
            drawCloudCol(c.x, 0, c.w, c.gy, true, c.bolt);
            drawCloudCol(c.x, c.gy + c.gh, c.w, H - SEA - c.gy - c.gh, false, false);
        });

        // ramas de olivo
        olives.forEach(function (o) {
            if (!o.taken) { drawOlive(o.x, o.y, o.ph); }
        });

        // lluvia
        ctx.strokeStyle = 'rgba(140,170,205,.45)';
        ctx.lineWidth = 1.4; ctx.lineCap = 'round';
        ctx.beginPath();
        rain.forEach(function (r) {
            ctx.moveTo(r.x, r.y); ctx.lineTo(r.x - 3, r.y + 9);
        });
        ctx.stroke();

        // mar
        var seaY = H - SEA;
        ctx.fillStyle = '#3a7ca5'; ctx.fillRect(0, seaY, W, SEA);
        ctx.fillStyle = '#55a0cc';
        for (var x = (seaX % 44) - 44; x < W + 44; x += 44) {
            ctx.beginPath(); ctx.arc(x, seaY, 12, Math.PI, 0); ctx.fill();
        }
        ctx.fillStyle = 'rgba(255,255,255,.35)';
        for (var x2 = ((seaX * 1.4) % 44) - 22; x2 < W + 44; x2 += 44) {
            ctx.beginPath(); ctx.arc(x2, seaY + 2, 4, Math.PI, 0); ctx.fill();
        }
        ctx.font = '22px serif'; ctx.textAlign = 'left'; ctx.textBaseline = 'alphabetic';
        ctx.fillText('🚢', 12, seaY - 2);

        drawDove();

        // plumas + popups
        parts.forEach(function (p) {
            ctx.fillStyle = 'rgba(255,255,255,' + Math.max(0, p.life) + ')';
            ctx.beginPath(); ctx.arc(p.x, p.y, p.s, 0, 7); ctx.fill();
        });
        popups.forEach(function (p) {
            ctx.globalAlpha = Math.max(0, p.life);
            ctx.font = '700 14px sans-serif'; ctx.textAlign = 'center';
            ctx.fillStyle = '#2f6b2f';
            ctx.strokeStyle = 'rgba(255,255,255,.8)'; ctx.lineWidth = 3;
            ctx.strokeText(p.txt, p.x, p.y);
            ctx.fillText(p.txt, p.x, p.y);
            ctx.globalAlpha = 1;
        });
    }

    var last = 0;
    function loop(ts) {
        var dt = Math.min(.05, (ts - last) / 1000) || .016;
        last = ts;
        step(dt);
        draw();
        raf = requestAnimationFrame(loop);
    }

    function showMenu() {
        alive_stop();
        menu.innerHTML = menuHTML();
        menu.style.display = 'flex';
        document.getElementById('pnGo').addEventListener('click', function (ev) {
            ev.stopPropagation();
            BFJ.snd('ok');
            beginFlight();
        });
        reset();
        draw();
    }

    function alive_stop() {
        cancelAnimationFrame(raf);
        flying = false;
    }

    function beginFlight() {
        reset();
        over = false; won = false; flying = false;
        var f = flight();
        menu.style.display = 'none';
        hudOl.textContent = '🌿 0' + (f.olivesNeeded ? '/' + f.olivesNeeded : '');
        hudM.textContent = '0 m';
        prog.style.display = f.endless ? 'none' : 'block';
        pFill.style.width = '0%'; pIco.style.left = '-8px';
        hint.hidden = false;
        last = performance.now();
        raf = requestAnimationFrame(loop);
    }

    // Controles: toque, clic, espacio/flecha — con fallback para Safari viejo
    function onTap(ev) {
        if (menu.style.display !== 'none') { return; }
        ev.preventDefault();
        flap();
    }
    if (window.PointerEvent) {
        stage.addEventListener('pointerdown', onTap);
    } else {
        stage.addEventListener('touchstart', onTap);
        stage.addEventListener('mousedown', onTap);
    }
    document.addEventListener('keydown', function (ev) {
        if (menu.style.display !== 'none' || ev.repeat) { return; }
        var k = ev.key || (ev.keyCode === 32 ? ' ' : (ev.keyCode === 38 ? 'ArrowUp' : ''));
        if (k === ' ' || k === 'ArrowUp' || k === 'ArrowDown') {
            ev.preventDefault();
            flap();
        }
    });

    showMenu();
});
