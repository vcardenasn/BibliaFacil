<?php

use Biblia\Bible\ApiBibleService;
use Biblia\Bible\VerseText;

// Bug real NTV: palabras pegadas en límites de nodos del JSON de API.Bible
// ("Davidy", "hogarni", "Señor;le" en Salmo 132 — nodos para/char hermanos
// sin espacio entre ellos).

return function (TestCase $t): void {
    $txt = fn (string $s, string $v = 'PSA.132.1') => [
        'type' => 'text', 'text' => $s, 'attrs' => ['verseId' => $v],
    ];
    $para = fn (array $items, string $style = 'p') => [
        'type' => 'tag', 'name' => 'para', 'attrs' => ['style' => $style], 'items' => $items,
    ];

    $t->run('api: límite de nodos hermanos inserta espacio', function () use ($t, $txt, $para) {
        // "acuérdate de David" + char(add) "y de todo lo que sufrió."
        $out = ApiBibleService::parseChapter([$para([
            $txt('Señor, acuérdate de David'),
            ['type' => 'tag', 'name' => 'char', 'attrs' => ['style' => 'add'], 'items' => [
                $txt('y de todo lo que sufrió.'),
            ]],
        ])]);
        $t->assertSame('Señor, acuérdate de David y de todo lo que sufrió.', $out[1]);
    });

    $t->run('api: líneas de poesía q1/q2 se separan con espacio', function () use ($t, $txt, $para) {
        // Salmo 132:3 NTV — dos <para style="qN"> seguidos en el mismo versículo.
        $out = ApiBibleService::parseChapter([
            $para([$txt('«No iré a mi hogar', 'PSA.132.3')], 'q1'),
            $para([$txt('ni me permitiré descansar;', 'PSA.132.3')], 'q2'),
            $para([$txt('no dejaré que mis ojos duerman', 'PSA.132.4')], 'q1'),
            $para([$txt('ni cerraré los párpados adormecidos', 'PSA.132.4')], 'q2'),
        ]);
        $t->assertSame('«No iré a mi hogar ni me permitiré descansar;', $out[3]);
        $t->assertSame('no dejaré que mis ojos duerman ni cerraré los párpados adormecidos', $out[4]);
    });

    $t->run('api: puntuación de corte NO recibe espacio', function () use ($t, $txt, $para) {
        $out = ApiBibleService::parseChapter([$para([
            $txt('hola mundo'),
            ['type' => 'tag', 'name' => 'char', 'attrs' => ['style' => 'add'], 'items' => [
                $txt(', sí'),
            ]],
        ])]);
        $t->assertSame('hola mundo, sí', $out[1]);
    });

    $t->run('api: tras ";" el siguiente fragmento sí se separa', function () use ($t, $txt, $para) {
        // "Señor;" + "le juró" → "Señor; le juró"
        $out = ApiBibleService::parseChapter([$para([
            $txt('Le hizo una promesa solemne al Señor;'),
            $txt('le juró al Poderoso de Israel:'),
        ])]);
        $t->assertSame('Le hizo una promesa solemne al Señor; le juró al Poderoso de Israel:', $out[1]);
    });

    $t->run('api: no duplica espacio cuando ya existe', function () use ($t, $txt, $para) {
        $out = ApiBibleService::parseChapter([$para([
            $txt('palabra '),
            $txt('siguiente'),
        ])]);
        $t->assertSame('palabra siguiente', $out[1]);
    });

    $t->run('api: apertura « en fragmento nuevo recibe espacio', function () use ($t, $txt, $para) {
        $out = ApiBibleService::parseChapter([$para([
            $txt('y dijo:'),
            $txt('«Escucha»'),
        ])]);
        $t->assertSame('y dijo: «Escucha»', $out[1]);
    });

    $t->run('api: sentinels wj no rompen la detección de espacio', function () use ($t, $txt, $para) {
        $out = ApiBibleService::parseChapter([$para([
            $txt('Y les dijo:'),
            ['type' => 'tag', 'name' => 'char', 'attrs' => ['style' => 'wj'], 'items' => [
                $txt('Yo soy el pan', 'JHN.6.35'),
            ]],
        ])]);
        // verseId distinto — ajusto el fixture: mismo versículo
        $out = ApiBibleService::parseChapter([$para([
            $txt('Y les dijo:'),
            ['type' => 'tag', 'name' => 'char', 'attrs' => ['style' => 'wj'], 'items' => [
                $txt('Yo soy el pan'),
            ]],
        ])]);
        $t->assertSame('Y les dijo: [wj]Yo soy el pan[/wj]', $out[1]);
        [$clean, $wj] = VerseText::split($out[1]);
        $t->assertSame('Y les dijo: Yo soy el pan', $clean);
        $t->assertSame([[12, 13]], json_decode((string) $wj, true));
    });
};
