<?php

// EPIC 23 / US-230..233 — hub de juegos: mapa, identidad por juego y desafío del día.

return function (TestCase $t): void {
    $games = require CONFIG_PATH . '/games.php';

    $render = static function () use ($games): string {
        ob_start();
        extract(['games' => $games]);
        include BASE_PATH . '/app/Views/juegos.php';
        return ob_get_clean();
    };

    $t->run('hub: mapa con un nodo enlazado por juego listo', function () use ($t, $games, $render) {
        $html = $render();
        $ready = count(array_filter($games, function ($g) { return !empty($g['ready']); }));
        $t->assertSame(1, substr_count($html, 'class="jh-path"'));
        $t->assertSame($ready, substr_count($html, 'class="jh-node"'));
        $t->assertSame($ready, substr_count($html, 'jh-node-ring'));
        foreach (array_keys($games) as $slug) {
            if (!empty($games[$slug]['ready'])) {
                $t->assertTrue(str_contains($html, 'data-slug="' . $slug . '"'), "falta nodo $slug");
            }
        }
    });

    $t->run('hub: cada nodo lleva el color propio del juego (--gc)', function () use ($t, $games, $render) {
        $html = $render();
        foreach ($games as $g) {
            if (!empty($g['ready'])) {
                $t->assertTrue(str_contains($html, '--gc:' . $g['color']), 'falta color ' . $g['color']);
            }
        }
    });

    $t->run('desafío del día: determinístico por fecha y enlaza al juego', function () use ($t, $games, $render) {
        $html = $render();
        $readySlugs = array_keys(array_filter($games, function ($g) { return !empty($g['ready']); }));
        $expected = $readySlugs[crc32(date('Ymd')) % count($readySlugs)];
        $t->assertTrue(str_contains($html, 'id="dailyCard"'));
        $t->assertTrue(str_contains($html, '/juegos/' . $expected . '?desafio=' . date('Ymd')));
        $t->assertTrue(str_contains($html, 'Desafío de hoy'));
        // sin JS sigue siendo un enlace válido
        $t->assertTrue(str_contains($html, '▶ Jugar'));
    });

    $t->run('shell de juego: expone la fecha del servidor para el desafío', function () use ($t, $games) {
        $html = (static function () use ($games): string {
            ob_start();
            extract(['game' => $games['trivia'], 'slug' => 'trivia']);
            include BASE_PATH . '/app/Views/juego.php';
            return ob_get_clean();
        })();
        $t->assertTrue(str_contains($html, 'data-daily="' . date('Ymd') . '"'));
        $t->assertTrue(str_contains($html, 'id="gameStars"'));
    });
};
