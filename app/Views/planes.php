<?php
// EPIC 05 / US-040 — índice de planes de lectura.
/** @var array $plans @var array $planTotals @var array|null $votd */
?>
<div class="page-hero">
    <h1><?= e(t('Planes de lectura')) ?></h1>
    <p><?= e(t('Recorridos guiados por la Biblia, a tu ritmo. Tu progreso se guarda en este dispositivo, sin crear una cuenta.')) ?></p>
</div>

<?php if ($votd): ?>
<a class="plan-devcard" href="<?= e(url($planVersion['code'] . '/' . $votd['book_slug'] . '/' . $votd['chapter'])) ?>?dev=<?= (int) $votd['verse'] ?>">
    <span class="plan-devcard-emoji" aria-hidden="true">📓</span>
    <span class="plan-devcard-t">
        <strong><?= e(t('Devocional de hoy')) ?></strong>
        <small><?= e(t($votd['book_name']) . ' ' . $votd['chapter'] . ':' . $votd['verse']) ?> — <?= e(t('escribe tus apuntes con las 4 preguntas guía')) ?></small>
    </span>
    <span class="plan-devcard-go" aria-hidden="true"><?= e(t('Abrir')) ?> →</span>
</a>
<?php endif; ?>

<div class="plan-cards">
    <?php foreach ($plans as $slug => $p): ?>
    <a class="card plan-card" href="<?= e(url('planes/' . $slug)) ?>">
        <span class="plan-emoji" aria-hidden="true"><?= e($p['emoji'] ?? '📖') ?></span>
        <strong><?= e(t($p['name'])) ?></strong>
        <span class="plan-desc"><?= e(t($p['desc'])) ?></span>
        <span class="plan-meta muted"><?= (int) $p['days'] ?> <?= e(t('días')) ?> · <?= (int) ($planTotals[$slug] ?? 0) ?> <?= e(t('capítulos')) ?></span>
        <span class="plan-prog" data-planprog="<?= e($slug) ?>" hidden></span>
    </a>
    <?php endforeach; ?>
</div>
