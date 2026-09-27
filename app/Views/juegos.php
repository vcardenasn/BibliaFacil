<?php // EPIC 17 + EPIC 23: hub con identidad por juego, nivel y mapa de camino (US-232). ?>
<div class="jh-hero">
    <div class="jh-rays" aria-hidden="true"></div>
    <h1><?= omoji('1F3AE', '', 'eager') ?> Juegos Bíblicos</h1>
    <p>Aprende la Palabra jugando — sigue el camino</p>
    <div class="jh-score">
        <span class="jh-stars" id="hubStars"><span aria-hidden="true">⭐</span> <strong id="totalStars">0</strong></span>
        <span class="jh-level-wrap">
            <span class="jh-level" id="levelBadge">🌱 Explorador</span>
            <span class="jh-levelbar" role="progressbar" aria-label="Progreso al siguiente nivel" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span id="levelBar"></span></span>
        </span>
    </div>
</div>

<?php
// US-233 — Desafío del día: juego determinístico por fecha (crc32 del día).
$readySlugs = array_keys(array_filter($games, function ($g) { return !empty($g['ready']); }));
$dailyDate = date('Ymd');
$dailySlug = $readySlugs[crc32($dailyDate) % count($readySlugs)];
$dailyGame = $games[$dailySlug];
?>
<a class="jh-daily" id="dailyCard" href="<?= e(url('juegos/' . $dailySlug . '?desafio=' . $dailyDate)) ?>"
    data-slug="<?= e($dailySlug) ?>" data-date="<?= $dailyDate ?>"
    style="--gc:<?= e($dailyGame['color'] ?? '#4dabf7') ?>">
    <span class="jh-daily-emoji" aria-hidden="true"><?= omoji('1F4C5', '', 'eager') ?></span>
    <span class="jh-daily-info">
        <span class="jh-daily-tag">Desafío de hoy · <?= date('d/m') ?></span>
        <strong><?= e($dailyGame['name']) ?></strong>
        <small id="dailyHint">Complétalo hoy y gana <span aria-hidden="true">⭐</span>×2</small>
    </span>
    <span class="jh-daily-state" id="dailyState"><span aria-hidden="true">▶</span> Jugar</span>
</a>

<div class="jh-path">
    <?php foreach ($games as $gslug => $g): ?>
        <?php if (!empty($g['ready'])): ?>
        <a class="jh-node" href="<?= e(url('juegos/' . $gslug)) ?>" data-slug="<?= e($gslug) ?>"
            style="--gc:<?= e($g['color'] ?? '#4dabf7') ?>">
            <span class="jh-node-ring" aria-hidden="true"><span class="jh-emoji"><?= omoji($g['img'] ?? '') ?></span></span>
            <span class="jh-node-info">
                <strong><?= e($g['name']) ?></strong>
                <small><?= e($g['desc']) ?></small>
                <span class="jh-best" data-best><span aria-hidden="true">☆</span> ¡Juega ya!</span>
            </span>
        </a>
        <?php else: ?>
        <div class="jh-node locked" aria-disabled="true">
            <span class="jh-node-ring" aria-hidden="true"><span class="jh-emoji"><?= omoji('1F512') ?></span></span>
            <span class="jh-node-info">
                <strong><?= e($g['name']) ?></strong>
                <small><?= e($g['desc']) ?></small>
                <span class="jh-best">Muy pronto</span>
            </span>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<h2 class="jh-sub"><span aria-hidden="true">🏆</span> Mi álbum de stickers</h2>
<div class="jh-stickers" id="stickerWall"></div>

<p class="jh-note">Tus estrellas se guardan en este dispositivo — sin cuentas <span aria-hidden="true">🌟</span></p>
<p class="jh-note">Ilustraciones de <a href="https://openmoji.org" rel="noopener" target="_blank">OpenMoji</a> — CC BY-SA 4.0</p>
