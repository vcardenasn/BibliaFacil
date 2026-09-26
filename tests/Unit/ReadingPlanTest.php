<?php

use Biblia\Bible\ReadingPlan;

// EPIC 05 / US-040, US-041 — reparto de capítulos en días + vista de plan.

return function (TestCase $t): void {
    $books = require CONFIG_PATH . '/books.php';
    $plans = require CONFIG_PATH . '/plans.php';

    $t->run('planes: reparten los capítulos correctos por testamento/selección', function () use ($t, $books, $plans) {
        $t->assertSame(1189, count(ReadingPlan::readings($plans['biblia-en-un-ano'], $books)));
        $t->assertSame(260, count(ReadingPlan::readings($plans['nuevo-testamento-90'], $books)));
        $t->assertSame(181, count(ReadingPlan::readings($plans['salmos-proverbios'], $books)));
    });

    $t->run('plan año: 365 días contiguos de Génesis 1 a Apocalipsis 22', function () use ($t, $books, $plans) {
        $days = ReadingPlan::days($plans['biblia-en-un-ano'], $books);
        $t->assertSame(365, count($days));
        $flat = [];
        foreach ($days as $d) { foreach ($d['items'] as $it) { $flat[] = $it['slug'] . '/' . $it['ch']; } }
        $t->assertSame(1189, count($flat));
        $t->assertSame('genesis/1', $flat[0]);
        $t->assertSame('apocalipsis/22', $flat[count($flat) - 1]);
        $t->assertTrue(str_starts_with($days[0]['label'], 'Génesis 1–'));
        $t->assertTrue(str_contains($days[364]['label'], 'Apocalipsis'));
    });

    $t->run('plan NT: día 1 empieza en Mateo 1', function () use ($t, $books, $plans) {
        $days = ReadingPlan::days($plans['nuevo-testamento-90'], $books);
        $t->assertSame(90, count($days));
        $t->assertSame('mateo', $days[0]['items'][0]['slug']);
        $t->assertSame(1, $days[0]['items'][0]['ch']);
    });

    $t->run('vista plan: lista de días, marcadores y barra de progreso', function () use ($t, $books, $plans) {
        $version = ['id' => 1, 'code' => 'rvr1909', 'name' => 'Reina-Valera 1909'];
        $days = ReadingPlan::days($plans['salmos-proverbios'], $books);
        $html = (static function (array $v): string {
            extract($v);
            ob_start();
            include BASE_PATH . '/app/Views/plan.php';
            return ob_get_clean();
        })([
            'plan' => $plans['salmos-proverbios'], 'slug' => 'salmos-proverbios',
            'planVersion' => $version, 'days' => $days,
        ]);
        $t->assertSame(31, substr_count($html, 'class="plan-day"'));
        $t->assertSame(31, substr_count($html, 'aria-pressed="false"'));
        $t->assertTrue(str_contains($html, 'id="planApp"'));
        $t->assertTrue(str_contains($html, 'role="progressbar"'));
        $t->assertTrue(str_contains($html, '/rvr1909/salmos/1'));
        $t->assertTrue(str_contains($html, '/rvr1909/proverbios/31'));
    });
};
