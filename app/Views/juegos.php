<?php // EPIC 17 + EPIC 23: hub con identidad por juego, nivel y mapa de camino (US-232). ?>
<div class="jh-hero">
    <div class="jh-rays" aria-hidden="true"></div>
    <h1>🎮 Juegos Bíblicos</h1>
    <p>Aprende la Palabra jugando — sigue el camino</p>
    <div class="jh-score">
        <span class="jh-stars" id="hubStars">⭐ <strong id="totalStars">0</strong></span>
        <span class="jh-level-wrap">
            <span class="jh-level" id="levelBadge">🌱 Explorador</span>
            <span class="jh-levelbar" role="progressbar" aria-label="Progreso al siguiente nivel" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span id="levelBar"></span></span>
        </span>
    </div>
</div>

<div class="jh-path">
    <?php foreach ($games as $gslug => $g): ?>
        <?php if (!empty($g['ready'])): ?>
        <a class="jh-node" href="<?= e(url('juegos/' . $gslug)) ?>" data-slug="<?= e($gslug) ?>"
            style="--gc:<?= e($g['color'] ?? '#4dabf7') ?>">
            <span class="jh-node-ring" aria-hidden="true"><span class="jh-emoji"><?= $g['emoji'] ?></span></span>
            <span class="jh-node-info">
                <strong><?= e($g['name']) ?></strong>
                <small><?= e($g['desc']) ?></small>
                <span class="jh-best" data-best>☆ ¡Juega ya!</span>
            </span>
        </a>
        <?php else: ?>
        <div class="jh-node locked" aria-disabled="true">
            <span class="jh-node-ring" aria-hidden="true"><span class="jh-emoji">🔒</span></span>
            <span class="jh-node-info">
                <strong><?= e($g['name']) ?></strong>
                <small><?= e($g['desc']) ?></small>
                <span class="jh-best">Muy pronto</span>
            </span>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<h2 class="jh-sub">🏆 Mi álbum de stickers</h2>
<div class="jh-stickers" id="stickerWall"></div>

<p class="jh-note">Tus estrellas se guardan en este dispositivo — sin cuentas 🌟</p>
