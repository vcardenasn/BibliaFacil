<?php
// EPIC 05 / US-041 — detalle de plan: día actual, marcar leído, progreso.
// El estado vive en localStorage (bf_plan_{slug}); la página sirve la
// estructura completa sin JS y el script solo pinta el progreso.
/** @var array $plan @var string $slug @var array $days @var array|null $planVersion */
$totalDays = count($days);
?>
<h1><span aria-hidden="true"><?= e($plan['emoji'] ?? '📖') ?></span> <?= e($plan['name']) ?></h1>
<p class="muted"><?= e($plan['desc']) ?></p>
<?php if (!empty($plan['intro'])): ?>
<p class="book-intro"><?= e($plan['intro']) ?></p>
<?php endif; ?>

<div id="planApp" data-slug="<?= e($slug) ?>">
    <section class="plan-status card" aria-label="Tu progreso">
        <p class="plan-progress-label"><strong id="planLabel">0 de <?= $totalDays ?> días</strong> completados</p>
        <div class="plan-bar" role="progressbar" aria-label="Progreso del plan" aria-valuemin="0" aria-valuemax="<?= $totalDays ?>" aria-valuenow="0"><span id="planBar" style="width:0%"></span></div>
        <p class="muted" id="planPace" role="status"></p>
        <div class="plan-btns">
            <button type="button" id="planStart" class="plan-btn">Empezar el plan</button>
            <button type="button" id="planReset" class="plan-btn ghost" hidden>Reiniciar progreso</button>
            <a id="planGo" class="plan-btn link" href="#d1">Ir al día 1 ↓</a>
        </div>
    </section>

    <ol class="plan-days">
        <?php foreach ($days as $d): ?>
        <li class="plan-day" id="d<?= (int) $d['n'] ?>">
            <button type="button" class="pd-check" data-day="<?= (int) $d['n'] ?>" aria-pressed="false"
                aria-label="Marcar el día <?= (int) $d['n'] ?> (<?= e($d['label']) ?>) como leído">
                <span class="pd-tick" aria-hidden="true">✓</span>
                <span class="pd-num">Día <?= (int) $d['n'] ?></span>
                <span class="pd-label"><?= e($d['label']) ?></span>
            </button>
            <span class="pd-links">
                <?php foreach ($d['items'] as $it): ?>
                <a href="<?= e(url("{$planVersion['code']}/{$it['slug']}/{$it['ch']}")) ?>"><?= e($it['name']) ?> <?= (int) $it['ch'] ?></a>
                <?php endforeach; ?>
            </span>
        </li>
        <?php endforeach; ?>
    </ol>
</div>

<p class="muted">Los capítulos se abren en <?= e($planVersion['name'] ?? 'tu versión') ?> — puedes cambiar de versión desde el selector del lector.</p>
