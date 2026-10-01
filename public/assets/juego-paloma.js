// La Paloma de Noé — runner estilo dino/flappy.
// La paloma aletea con toque/clic/espacio, esquiva las nubes de la tormenta
// y recoge ramas de olivo. Canvas 2D con render a devicePixelRatio (nítido en
// retina) y controles con fallback touchstart/keyCode para Safari viejo.
BFJ.define('paloma', function (el) {
    el.innerHTML =
        '<div class="pn-stage" id="pnStage">' +
        '<canvas class="pn-cv" id="pnCv" width="480" height="320" ' +
        'aria-label="La Paloma de Noé: esquiva las nubes y recoge ramas de olivo"></canvas>' +
        '<div class="pn-hud" aria-hidden="true">' +
        '<span id="pnOl">🌿 0</span><span id="pnM">0 m</span>' +
        '</div>' +
        '<div class="pn-menu" id="pnMenu">' +
        '<p class="pn-emo" aria-hidden="true">🕊️</p>' +
        '<p class="pn-t">La Paloma de Noé</p>' +
        '<p class="pn-s">El diluvio cubrió la tierra. Ayuda a la paloma a volar entre ' +
        'las nubes de la tormenta y encontrar la rama de olivo.</p>' +
        '<p class="pn-k">👆 Toca la pantalla o presiona <kbd>espacio</kbd> para volar</p>' +
        '<button type="button" class="jbtn jbtn-main" id="pnGo">▶ ¡A volar!</button>' +
        '</div></div>';

    var cv = document.getElementById('pnCv');
    var ctx = cv.getContext('2d');
    var stage = document.getElementById('pnStage');
    var menu = document.getElementById('pnMenu');
    var hudOl = document.getElementById('pnOl');
    var hudM = document.getElementById('pnM');
    var W = 480, H = 320;
    var SEA = 34, SKY = 8;

    // Render retina: backing store ×dpr, lógica siempre 480×320
    var DPR = Math.min(2, window.devicePixelRatio || 1);
    cv.width = W * DPR; cv.height = H * DPR;
    ctx.scale(DPR, DPR);

    var raf = null, alive = false, over = false;
    var dove, clouds, olives, parts, rain, bgs, t, dist, got, speed, spawnIn, seaX, flapT;

    function reset() {
        dove = { x: 90, y: H * .45, vy: 0, r: 13 };
        clouds = []; olives = []; parts = []; rain = [];
        bgs = [
            { x: 60, y: 46, s: 1.0 }, { x: 230, y: 30, s: .7 },
            { x: 390, y: 60, s: 1.2 }, { x: 520, y: 38, s: .8 }
        ];
        t = 0; dist = 0; got = 0; speed = 120; spawnIn = 480; seaX = 0; flapT = 0;
    }

    function flap() {
        if (!alive || over) { return; }
        dove.vy = -190;
        flapT = .55; // aleteo fuerte tras el impulso
        BFJ.snd('click');
    }

    function spawnCloud() {
        var gap = Math.max(78, H * .34 - t * .6);
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

    function die() {
        if (over) { return; }
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
        setTimeout(end, 900);
    }

    function end() {
        cancelAnimationFrame(raf);
        var m = Math.floor(dist);
        var stars = Math.min(10, got + Math.floor(m / 120));
        BFJ.celebrate({
            slug: 'paloma', stars: stars,
            emoji: got >= 3 ? '🕊️' : (m >= 200 ? '🌈' : '⛈️'),
            title: got >= 3 ? '¡Encontró la rama de olivo!' : (m >= 200 ? '¡Buen vuelo!' : '¡Sigue intentando!'),
            extra: 'Volaste ' + m + ' m' + (got ? ' · ' + got + ' rama' + (got === 1 ? '' : 's') + ' de olivo 🌿' : ''),
            onAgain: function () { start(); }
        });
    }

    function step(dt) {
        t += dt * 1000;
        flapT = Math.max(0, flapT - dt);
        speed = Math.min(300, 120 + t * .014);
        dist += speed * dt * .12;
        seaX -= speed * dt;

        // paloma
        dove.vy += 640 * dt;
        dove.y += dove.vy * dt;
        if (dove.y - dove.r < SKY) { dove.y = SKY + dove.r; dove.vy = 0; }
        if (dove.y + dove.r > H - SEA) { dove.y = H - SEA - dove.r; die(); }

        // nubes obstáculo
        spawnIn -= speed * dt;
        if (spawnIn <= 0) { spawnCloud(); spawnIn = 260 + Math.random() * 120; }
        for (var i = clouds.length - 1; i >= 0; i--) {
            clouds[i].x -= speed * dt;
            if (!over && cloudHits(clouds[i].x, clouds[i].gy, clouds[i].gh, clouds[i].w)) { die(); }
            if (clouds[i].x < -80) { clouds.splice(i, 1); }
        }

        // ramas de olivo
        for (var j = olives.length - 1; j >= 0; j--) {
            var o = olives[j];
            o.x -= speed * dt;
            if (!o.taken && !over && hit(dove, { x: o.x, y: o.y, r: 11 })) {
                o.taken = true; got++;
                hudOl.textContent = '🌿 ' + got;
                BFJ.snd('ok');
            }
            if (o.x < -30) { olives.splice(j, 1); }
        }

        // lluvia de la tormenta
        if (rain.length < 40 && Math.random() < .5) {
            rain.push({ x: Math.random() * (W + 40), y: -10 });
        }
        for (var r = rain.length - 1; r >= 0; r--) {
            rain[r].y += 340 * dt; rain[r].x -= 60 * dt;
            if (rain[r].y > H - SEA) { rain.splice(r, 1); }
        }

        // nubes de fondo (parallax)
        bgs.forEach(function (b) {
            b.x -= speed * dt * .18;
            if (b.x < -60) { b.x = W + 60; b.y = 24 + Math.random() * 50; }
        });

        // plumas del choque
        for (var k = parts.length - 1; k >= 0; k--) {
            var p = parts[k];
            p.x += p.vx * dt; p.y += p.vy * dt; p.vy += 300 * dt; p.life -= dt * 1.4;
            if (p.life <= 0) { parts.splice(k, 1); }
        }

        hudM.textContent = Math.floor(dist) + ' m';
    }

    // ---------- Dibujo ----------

    function puff(x, y, r) {
        ctx.beginPath(); ctx.arc(x, y, r, 0, 7); ctx.fill();
    }

    function drawCloudCol(x, y, w, h, top, bolt) {
        var g = ctx.createLinearGradient(0, y, 0, y + (top ? h : -h) * (top ? 1 : -1));
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
        // rayo bajo la nube superior
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
        // tallo
        ctx.strokeStyle = '#5b8c3e'; ctx.lineWidth = 2; ctx.lineCap = 'round';
        ctx.beginPath(); ctx.moveTo(-9, 6); ctx.quadraticCurveTo(0, -2, 9, -7); ctx.stroke();
        // hojas
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

    function drawDove() {
        var bob = Math.sin(t * .006) * 2;
        var rot = Math.max(-.5, Math.min(.7, dove.vy / 320));
        // el ala aletea fuerte tras cada impulso, suave en planeo
        var wSpd = flapT > 0 ? .09 : .02;
        var wing = Math.sin(t * wSpd) * (flapT > 0 ? .95 : .3) - .25;

        ctx.save();
        ctx.translate(dove.x, dove.y + bob * .3);
        ctx.rotate(rot);

        // cola (abanico)
        ctx.fillStyle = '#dde5ec';
        ctx.beginPath();
        ctx.moveTo(-10, -1); ctx.lineTo(-23, -7); ctx.lineTo(-21, -1);
        ctx.lineTo(-23, 5); ctx.lineTo(-10, 3);
        ctx.closePath(); ctx.fill();

        // ala trasera (aleteo)
        ctx.save();
        ctx.translate(-2, -3);
        ctx.rotate(-wing * .8 - .2);
        ctx.fillStyle = '#c9d4e0';
        ctx.scale(1, .5);
        ctx.beginPath(); ctx.arc(-3, -8, 12, 0, 7); ctx.fill();
        ctx.restore();

        // cuerpo
        ctx.save();
        ctx.scale(1.35, .95);
        var bg = ctx.createRadialGradient(-2, -2, 2, 0, 0, 15);
        bg.addColorStop(0, '#ffffff'); bg.addColorStop(1, '#e2e9f0');
        ctx.fillStyle = bg;
        ctx.beginPath(); ctx.arc(0, 0, 11, 0, 7); ctx.fill();
        ctx.restore();

        // ala delantera (aleteo, encima del cuerpo)
        ctx.save();
        ctx.translate(-1, -4);
        ctx.rotate(-wing);
        ctx.fillStyle = '#f2f6fa';
        ctx.strokeStyle = '#b9c6d4'; ctx.lineWidth = 1;
        ctx.scale(1, .55);
        ctx.beginPath(); ctx.arc(-4, -9, 13, 0, 7); ctx.fill(); ctx.stroke();
        ctx.restore();

        // cabeza + pico + ojo
        ctx.fillStyle = '#f6f9fc';
        ctx.beginPath(); ctx.arc(10, -7, 6.5, 0, 7); ctx.fill();
        ctx.fillStyle = '#f0a030';
        ctx.beginPath(); ctx.moveTo(15, -8); ctx.lineTo(22, -6); ctx.lineTo(15, -5); ctx.closePath(); ctx.fill();
        ctx.fillStyle = '#2b3a4a';
        ctx.beginPath(); ctx.arc(11.5, -8.5, 1.4, 0, 7); ctx.fill();

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
        // cielo
        var sky = ctx.createLinearGradient(0, 0, 0, H);
        sky.addColorStop(0, '#7fb2e0'); sky.addColorStop(.65, '#b9d6f0'); sky.addColorStop(1, '#dcecf9');
        ctx.fillStyle = sky; ctx.fillRect(0, 0, W, H);
        // sol con halo
        ctx.fillStyle = 'rgba(247,201,72,.28)';
        ctx.beginPath(); ctx.arc(W - 58, 42, 36, 0, 7); ctx.fill();
        ctx.fillStyle = '#f7c948';
        ctx.beginPath(); ctx.arc(W - 58, 42, 22, 0, 7); ctx.fill();

        // nubes lejanas (parallax)
        ctx.fillStyle = 'rgba(255,255,255,.5)';
        bgs.forEach(function (b) {
            ctx.save(); ctx.translate(b.x, b.y); ctx.scale(b.s, b.s * .8);
            puff(0, 0, 14); puff(16, 2, 11); puff(-15, 3, 10);
            ctx.restore();
        });

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

        // mar — dos capas + espuma
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
        // el arca a lo lejos
        ctx.font = '22px serif'; ctx.textAlign = 'left'; ctx.textBaseline = 'alphabetic';
        ctx.fillText('�', 12, seaY - 2);

        drawDove();

        // plumas del choque
        parts.forEach(function (p) {
            ctx.fillStyle = 'rgba(255,255,255,' + Math.max(0, p.life) + ')';
            ctx.beginPath(); ctx.arc(p.x, p.y, p.s, 0, 7); ctx.fill();
        });
    }

    var last = 0;
    function loop(ts) {
        if (!alive) { return; }
        var dt = Math.min(.05, (ts - last) / 1000) || .016;
        last = ts;
        step(dt);
        draw();
        raf = requestAnimationFrame(loop);
    }

    function start() {
        reset();
        over = false; alive = true;
        menu.style.display = 'none';
        hudOl.textContent = '🌿 0'; hudM.textContent = '0 m';
        last = performance.now();
        raf = requestAnimationFrame(loop);
    }

    // Controles: toque, clic, espacio/flecha — con fallback para Safari viejo
    // (Pointer Events y ev.key no existen en iOS <13)
    function onTap(ev) {
        if (!alive) { return; }
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
        if (!alive || ev.repeat) { return; }
        var k = ev.key || (ev.keyCode === 32 ? ' ' : (ev.keyCode === 38 ? 'ArrowUp' : ''));
        if (k === ' ' || k === 'ArrowUp' || k === 'ArrowDown') {
            ev.preventDefault();
            flap();
        }
    });
    document.getElementById('pnGo').addEventListener('click', function (ev) {
        ev.stopPropagation();
        BFJ.snd('ok');
        start();
    });

    // Escena quieta detrás del menú de inicio
    reset();
    draw();
});
