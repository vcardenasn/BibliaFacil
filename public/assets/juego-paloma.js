// La Paloma de Noé — runner estilo dino/flappy.
// La paloma aletea con toque/clic/espacio, esquiva las nubes de la tormenta
// y recoge ramas de olivo. Canvas 2D puro (compatible con Safari viejo).
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
    var W = cv.width, H = cv.height;
    var SEA = 34;           // alto del mar (abajo)
    var SKY = 8;            // techo
    var raf = null, alive = false, over = false;

    // Estado
    var dove, clouds, olives, parts, t, dist, got, speed, spawnIn, seaX;

    function reset() {
        dove = { x: 90, y: H * .45, vy: 0, r: 13 };
        clouds = []; olives = []; parts = [];
        t = 0; dist = 0; got = 0; speed = 120; spawnIn = 480; seaX = 0;
    }

    function flap() {
        if (!alive || over) { return; }
        dove.vy = -190;
        BFJ.snd('click');
    }

    function spawnCloud() {
        var gap = Math.max(78, H * .34 - t * .6); // el hueco se va cerrando
        var cy = 40 + Math.random() * (H - SEA - 80 - gap);
        clouds.push({ x: W + 40, gy: cy, gh: gap, w: 56 });
        if (Math.random() < .75) {
            olives.push({
                x: W + 40 + 28,
                y: cy + gap / 2 + (Math.random() - .5) * gap * .4,
                taken: false
            });
        }
    }

    function hit(a, b) {
        var dx = a.x - b.x, dy = a.y - b.y;
        return dx * dx + dy * dy < (a.r + b.r) * (a.r + b.r);
    }

    function cloudHits(cx, gy, gh, cw) {
        var dx = dove.x, r = dove.r * .82;
        // columna superior: 0..gy ; inferior: gy+gh..mar
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
        for (var i = 0; i < 10; i++) {
            parts.push({
                x: dove.x, y: dove.y,
                vx: (Math.random() - .5) * 160, vy: -Math.random() * 120,
                s: 2 + Math.random() * 3, life: 1
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
        speed = Math.min(300, 120 + t * .014);
        dist += speed * dt * .12;
        seaX -= speed * dt;

        // paloma
        dove.vy += 640 * dt;
        dove.y += dove.vy * dt;
        if (dove.y - dove.r < SKY) { dove.y = SKY + dove.r; dove.vy = 0; }
        if (dove.y + dove.r > H - SEA) { dove.y = H - SEA - dove.r; die(); }

        // nubes
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

        // plumas del choque
        for (var k = parts.length - 1; k >= 0; k--) {
            var p = parts[k];
            p.x += p.vx * dt; p.y += p.vy * dt; p.vy += 300 * dt; p.life -= dt * 1.4;
            if (p.life <= 0) { parts.splice(k, 1); }
        }

        hudM.textContent = Math.floor(dist) + ' m';
    }

    function drawCloud(x, y, w, h, top) {
        ctx.fillStyle = 'rgba(255,255,255,.94)';
        ctx.strokeStyle = 'rgba(120,140,170,.5)';
        ctx.lineWidth = 2;
        var n = Math.max(2, Math.round(h / 26));
        for (var i = 0; i < n; i++) {
            var cy = top ? (y + h - 14 - i * 24) : (y + 14 + i * 24);
            ctx.beginPath();
            ctx.arc(x + w * .28, cy, 17, 0, 7);
            ctx.arc(x + w * .62, cy + (i % 2 ? 4 : -3), 20, 0, 7);
            ctx.arc(x + w * .88, cy, 14, 0, 7);
            ctx.fill(); ctx.stroke();
        }
        // rayo de tormenta en algunas nubes
        if (top && h > 70 && (Math.round(x / 13) % 3 === 0)) {
            ctx.fillStyle = '#f5c518';
            ctx.beginPath();
            var bx = x + w * .55, by = y + h - 6;
            ctx.moveTo(bx, by); ctx.lineTo(bx - 7, by + 16); ctx.lineTo(bx - 1, by + 16);
            ctx.lineTo(bx - 8, by + 30); ctx.lineTo(bx + 1, by + 14); ctx.lineTo(bx - 4, by + 14);
            ctx.closePath(); ctx.fill();
        }
    }

    function draw() {
        // cielo
        var sky = ctx.createLinearGradient(0, 0, 0, H);
        sky.addColorStop(0, '#9ec8ee'); sky.addColorStop(.7, '#cfe6f7'); sky.addColorStop(1, '#e8f3fb');
        ctx.fillStyle = sky; ctx.fillRect(0, 0, W, H);
        // sol
        ctx.fillStyle = 'rgba(245,197,24,.9)';
        ctx.beginPath(); ctx.arc(W - 60, 44, 22, 0, 7); ctx.fill();
        ctx.fillStyle = 'rgba(245,197,24,.25)';
        ctx.beginPath(); ctx.arc(W - 60, 44, 34, 0, 7); ctx.fill();

        // nubes obstáculo
        clouds.forEach(function (c) {
            drawCloud(c.x, 0, c.w, c.gy, true);
            drawCloud(c.x, c.gy + c.gh, c.w, H - SEA - c.gy - c.gh, false);
        });

        // ramas de olivo
        olives.forEach(function (o) {
            if (o.taken) { return; }
            ctx.save();
            ctx.translate(o.x, o.y);
            ctx.rotate(Math.sin(t * .005 + o.x) * .2);
            ctx.font = '20px serif'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.fillText('🌿', 0, 0);
            ctx.restore();
        });

        // mar
        var seaY = H - SEA;
        ctx.fillStyle = '#3d7ea6'; ctx.fillRect(0, seaY, W, SEA);
        ctx.fillStyle = '#57a0c9';
        for (var x = (seaX % 36) - 36; x < W + 36; x += 36) {
            ctx.beginPath(); ctx.arc(x, seaY, 10, Math.PI, 0); ctx.fill();
        }
        // el arca a lo lejos
        ctx.font = '22px serif'; ctx.textAlign = 'left'; ctx.textBaseline = 'alphabetic';
        ctx.fillText('🚢', 14, seaY - 2);

        // paloma
        ctx.save();
        ctx.translate(dove.x, dove.y);
        ctx.rotate(Math.max(-.5, Math.min(.7, dove.vy / 320)));
        ctx.font = '26px serif'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText('🕊️', 0, 0);
        ctx.restore();

        // plumas
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

    // Dibuja la escena quieta detrás del menú de inicio
    reset();
    draw();
});
