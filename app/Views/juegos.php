<div class="jh-hero">
    <h1>🎮 Juegos Bíblicos</h1>
    <p>Aprende la Palabra jugando</p>
    <div class="jh-score">
        <span class="jh-stars">⭐ <strong id="totalStars">0</strong></span>
        <span class="jh-level" id="levelBadge">🌱 Explorador</span>
    </div>
</div>

<div class="jh-grid">
    <?php foreach ($games as $gslug => $g): ?>
        <?php if (!empty($g['ready'])): ?>
        <a class="jh-card" href="<?= e(url('juegos/' . $gslug)) ?>" data-slug="<?= e($gslug) ?>">
            <span class="jh-emoji"><?= $g['emoji'] ?></span>
            <strong><?= e($g['name']) ?></strong>
            <small><?= e($g['desc']) ?></small>
            <span class="jh-best" data-best>☆ ¡Juega ya!</span>
        </a>
        <?php else: ?>
        <div class="jh-card locked" aria-disabled="true">
            <span class="jh-emoji"><?= $g['emoji'] ?></span>
            <strong><?= e($g['name']) ?></strong>
            <small><?= e($g['desc']) ?></small>
            <span class="jh-best">🔒 Muy pronto</span>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<p class="jh-note">Tus estrellas se guardan en este dispositivo — sin cuentas 🌟</p>
