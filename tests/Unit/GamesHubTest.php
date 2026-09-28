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

    $t->run('hub: nodos usan imágenes ilustradas decorativas', function () use ($t, $games, $render) {
        $html = $render();
        $ready = count(array_filter($games, function ($g) { return !empty($g['ready']); }));
        // hero + daily + uno por nodo listo
        $t->assertTrue(substr_count($html, 'assets/omoji/') >= $ready + 2);
        $t->assertTrue(str_contains($html, 'aria-hidden="true"'));
        foreach ($games as $g) {
            if (!empty($g['ready'])) {
                $f = BASE_PATH . '/public/assets/omoji/' . $g['img'] . '.svg';
                $t->assertTrue(is_file($f), 'falta omoji ' . $g['img']);
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
        $t->assertTrue(str_contains($html, 'Jugar</span>'));
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

    $t->run('versículo por niveles: sets del API mapean a libros reales', function () use ($t) {
        $index = file_get_contents(BASE_PATH . '/public/index.php');
        // Extrae y evalúa el mapa $vsets = ['slug' => [ords…]] del endpoint del juego
        $t->assertTrue((bool) preg_match('/\$vsets\s*=\s*(\[.*?\]);/s', $index, $m));
        $vsets = eval('return ' . $m[1] . ';');
        $t->assertSame(7, count($vsets), 'deben existir 7 bloques de nivel (6 sets + biblia)');
        $books = require CONFIG_PATH . '/books.php';
        $ords = array_map(static function ($b) { return (int) $b['ord']; }, $books);
        foreach ($vsets as $set => $list) {
            if ($set !== 'biblia') {
                $t->assertTrue(count($list) > 0, "set $set vacío");
            }
            foreach ($list as $o) {
                $t->assertTrue(in_array((int) $o, $ords, true), "ord inválido en $set: $o");
            }
        }
        // El juego pide ?set= y los sets JS coinciden con los del API
        $js = file_get_contents(BASE_PATH . '/public/assets/juego-versiculo.js');
        foreach (array_keys($vsets) as $set) {
            $t->assertTrue(str_contains($js, "'" . $set . "'"), "falta set $set en el JS");
        }
        $t->assertTrue(str_contains($js, 'api/versiculo?n='));
        $t->assertTrue(str_contains($js, 'BFJ.levels.pass'));
    });
};
