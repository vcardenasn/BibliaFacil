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

<?php
// Regiones del mapa de progreso: el viaje bíblico Edén → Galilea.
// Cada juego declara 'region' en config/games.php; se muestran en orden
// de catálogo y agrupan tramos del camino con banner + contador de avance.
$JH_REGIONS = [
    'comienzos' => ['emoji' => '1F334', 'name' => 'Los Comienzos',    'ref' => 'Génesis 1–9'],
    'desierto'  => ['emoji' => '1F3DC', 'name' => 'El Desierto',      'ref' => 'Éxodo'],
    'historias' => ['emoji' => '1F4DC', 'name' => 'Las Historias',    'ref' => 'Los héroes de la fe'],
    'prometida' => ['emoji' => '1F3D6', 'name' => 'Tierra Prometida', 'ref' => 'Josué — Reyes'],
    'galilea'   => ['emoji' => '26F5',  'name' => 'Junto al Lago',    'ref' => 'Los Evangelios'],
];
?>
<div class="jh-path">
    <?php $nAlt = 0; $lastRegion = null; ?>
    <?php foreach ($games as $gslug => $g): ?>
        <?php
        $region = $g['region'] ?? null;
        if ($region && $region !== $lastRegion && isset($JH_REGIONS[$region])):
            $lastRegion = $region;
            $rg = $JH_REGIONS[$region];
            // slugs de esta región para el contador "n/m" que llena el JS
            $regSlugs = array_keys(array_filter($games, function ($x) use ($region) {
                return ($x['region'] ?? null) === $region;
            }));
        ?>
        <div class="jh-region" data-reg-slugs="<?= e(implode(',', $regSlugs)) ?>">
            <span class="jh-region-tag">
                <?= omoji($rg['emoji']) ?> <?= e($rg['name']) ?>
                <small><?= e($rg['ref']) ?></small>
                <b class="jh-region-n" hidden>0/<?= count($regSlugs) ?></b>
            </span>
        </div>
        <?php endif; ?>
        <?php if (!empty($g['ready'])): ?>
        <a class="jh-node <?= $nAlt++ % 2 ? 'alt-r' : 'alt-l' ?>" href="<?= e(url('juegos/' . $gslug)) ?>" data-slug="<?= e($gslug) ?>"
            style="--gc:<?= e($g['color'] ?? '#4dabf7') ?>">
            <span class="jh-node-ring" aria-hidden="true"><span class="jh-emoji"><?= omoji($g['img'] ?? '') ?></span></span>
            <span class="jh-node-info">
                <strong><?= e($g['name']) ?></strong>
                <small><?= e($g['desc']) ?></small>
                <span class="jh-best" data-best><span aria-hidden="true">☆</span> ¡Juega ya!</span>
            </span>
        </a>
        <?php else: ?>
        <div class="jh-node locked <?= $nAlt++ % 2 ? 'alt-r' : 'alt-l' ?>" aria-disabled="true">
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
