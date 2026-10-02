<?php
// EPIC 05 / US-040 — índice de planes de lectura.
/** @var array $plans @var array $planTotals @var array|null $votd */
?>
<div class="page-hero">
    <h1>Planes de lectura</h1>
    <p>Recorridos guiados por la Biblia, a tu ritmo. Tu progreso se guarda en este dispositivo, sin crear una cuenta.</p>
</div>

<?php if ($votd): ?>
<a class="plan-devcard" href="<?= e(url($planVersion['code'] . '/' . $votd['book_slug'] . '/' . $votd['chapter'])) ?>?dev=<?= (int) $votd['verse'] ?>">
    <span class="plan-devcard-emoji" aria-hidden="true">📓</span>
    <span class="plan-devcard-t">
        <strong>Devocional de hoy</strong>
        <small><?= e($votd['book_name'] . ' ' . $votd['chapter'] . ':' . $votd['verse']) ?> — escribe tus apuntes con las 4 preguntas guía</small>
    </span>
    <span class="plan-devcard-go" aria-hidden="true">Abrir →</span>
</a>
<?php endif; ?>

<div class="plan-cards">
    <?php foreach ($plans as $slug => $p): ?>
    <a class="card plan-card" href="<?= e(url('planes/' . $slug)) ?>">
        <span class="plan-emoji" aria-hidden="true"><?= e($p['emoji'] ?? '📖') ?></span>
        <strong><?= e($p['name']) ?></strong>
        <span class="plan-desc"><?= e($p['desc']) ?></span>
        <span class="plan-meta muted"><?= (int) $p['days'] ?> días · <?= (int) ($planTotals[$slug] ?? 0) ?> capítulos</span>
        <span class="plan-prog" data-planprog="<?= e($slug) ?>" hidden></span>
    </a>
    <?php endforeach; ?>
</div>
