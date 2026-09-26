<div class="jg-shell">
    <div class="jg-head">
        <a class="nav-btn" href="<?= e(url('juegos')) ?>">← Juegos</a>
        <h1><?= $game['emoji'] ?> <?= e($game['name']) ?></h1>
        <span class="jh-stars">⭐ <strong id="gameStars">0</strong></span>
    </div>
    <div id="gameApp" data-game="<?= e($slug) ?>" data-daily="<?= date('Ymd') ?>"></div>
</div>
