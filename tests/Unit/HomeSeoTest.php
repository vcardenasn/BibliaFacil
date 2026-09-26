<?php

use Biblia\Core\Seo;

return function (TestCase $t): void {
    $t->run('home: metadata indexable con canonical y datos estructurados', function () use ($t) {
        $server = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'ejemplo.org';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/';
        $meta = Seo::build('home', [
            'votd' => ['book_slug' => 'juan', 'chapter' => 3, 'verse' => 16],
            'votdVersion' => ['code' => 'rvr1909'],
        ]);
        $empty = Seo::build('home', []);
        $_SERVER = $server;
        $t->assertSame(false, $meta['noindex']);
        $t->assertSame('http://ejemplo.org/', $meta['canonical']);
        $t->assertSame('http://ejemplo.org/img/rvr1909/juan/3/16', $meta['image']);
        $t->assertSame('WebSite', $meta['jsonld'][0]['@type']);
        $t->assertSame(null, $empty['image']);
        $t->assertTrue(str_contains($meta['desc'], 'sin anuncios'));
    });

    $t->run('temas y versículos: compartir usa el slug de la ruta', function () use ($t) {
        $tema = (require __DIR__ . '/../../config/temas.php')['amor'];
        $entry = (require __DIR__ . '/../../config/versiculos.php')['juan-3-16'];
        $version = ['code' => 'rvr1909', 'name' => 'Reina-Valera 1909'];
        $render = static function (string $view, array $vars): string {
            extract($vars);
            ob_start();
            try {
                include __DIR__ . '/../../app/Views/' . $view . '.php';
                return ob_get_clean();
            } catch (Throwable $e) {
                ob_end_clean();
                throw $e;
            }
        };
        $themeHtml = $render('tema', ['tema' => $tema, 'slug' => 'amor', 'version' => $version, 'verses' => []]);
        $verseHtml = $render('versiculo', ['entry' => $entry, 'slug' => 'juan-3-16', 'texts' => []]);
        $t->assertTrue(str_contains($themeHtml, '/temas/amor'));
        $t->assertTrue(str_contains($verseHtml, '/versiculo/juan-3-16'));
    });

    $t->run('vista fallida: descarta HTML parcial antes de renderizar error', function () use ($t) {
        $level = ob_get_level();
        ob_start();
        try {
            view('versiculo', ['entry' => ['title' => 'Prueba', 'context' => 'Prueba'], 'texts' => []]);
        } catch (Throwable) {
        }
        while (ob_get_level() > $level + 1) { ob_end_clean(); }
        $html = ob_get_clean();
        $t->assertSame('', $html);
    });

    $t->run('libros: 66 enlaces visibles sin JS y SEO de versión intacto', function () use ($t) {
        $books = require __DIR__ . '/../../config/books.php';
        $version = ['code' => 'rvr1909', 'id' => 1, 'name' => 'Reina-Valera 1909'];
        $versions = [$version];
        $html = (static function (array $books, array $version, array $versions): string {
            $votd = null;
            ob_start();
            include __DIR__ . '/../../app/Views/books.php';
            return ob_get_clean();
        })($books, $version, $versions);
        $t->assertSame(66, count($books));
        $t->assertSame(66, substr_count($html, '<li><a href='));
        $t->assertTrue(str_contains($html, 'id="books-at"'));
        $t->assertTrue(str_contains($html, 'id="books-nt"'));
        $t->assertTrue(str_contains($html, 'id="book-filter"'));
        $t->assertTrue(str_contains($html, '/rvr1909/juan/1'));
        $server = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'ejemplo.org';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/rvr1909';
        $meta = Seo::build('books', ['version' => $version, 'versions' => $versions]);
        $_SERVER = $server;
        $t->assertFalse($meta['noindex']);
        $t->assertSame('http://ejemplo.org/rvr1909', $meta['canonical']);
    });

    $t->run('mias: filtros como radiogroup, filtro por libro y estado anunciable', function () use ($t) {
        $html = (static function (): string {
            ob_start();
            include __DIR__ . '/../../app/Views/mias.php';
            return ob_get_clean();
        })();
        $t->assertTrue(str_contains($html, 'role="radiogroup"'));
        $t->assertTrue(str_contains($html, 'role="radio"'));
        $t->assertTrue(str_contains($html, 'aria-checked="true"'));
        $t->assertTrue(str_contains($html, 'id="miasBook"'));
        $t->assertTrue(str_contains($html, 'id="miasCount"'));
        $t->assertTrue(str_contains($html, 'role="status"'));
        $t->assertTrue(str_contains($html, 'id="miasImportBtn"'));
        $t->assertFalse(str_contains($html, '<label for="miasImport"'));
        $t->assertTrue(str_contains($html, 'id="histBox"'));
        $t->assertTrue(str_contains($html, 'id="histList"'));
    });

    $t->run('comparar: dos columnas alineadas, canonical propio y noindex', function () use ($t) {
        $va = ['id' => 1, 'code' => 'rvr1909', 'name' => 'Reina-Valera 1909', 'copyright' => 'Dominio público'];
        $vb = ['id' => 2, 'code' => 'onbv', 'name' => 'ONBV', 'copyright' => null];
        $html = (static function (array $v): string {
            extract($v);
            ob_start();
            include __DIR__ . '/../../app/Views/comparar.php';
            return ob_get_clean();
        })([
            'versions' => [$va, $vb],
            'version' => $va,
            'book' => ['slug' => 'juan', 'name' => 'Juan', 'chapters' => 21],
            'chapter' => 3,
            'va' => $va, 'vb' => $vb,
            'versesA' => [16 => ['verse' => 16, 'text' => 'De tal manera amó…', 'wj' => null]],
            'versesB' => [16 => ['verse' => 16, 'text' => 'Porque tanto amó…', 'wj' => null], 17 => ['verse' => 17, 'text' => 'x', 'wj' => null]],
            'nav' => ['prev' => 'comparar/juan/2/rvr1909/onbv', 'next' => 'comparar/juan/4/rvr1909/onbv'],
        ]);
        $t->assertTrue(str_contains($html, 'class="cmp-grid"'));
        $t->assertTrue(str_contains($html, 'id="v16"'));
        $t->assertTrue(str_contains($html, 'comparar/juan/4/rvr1909/onbv'));
        $t->assertTrue(str_contains($html, '/rvr1909/juan/3'));
        $t->assertTrue(str_contains($html, 'Dominio público'));

        $pick = (static function (array $v): string {
            extract($v);
            ob_start();
            include __DIR__ . '/../../app/Views/comparar.php';
            return ob_get_clean();
        })(['versions' => [$va, $vb], 'books' => [['slug' => 'juan', 'name' => 'Juan']], 'book' => null, 'chapter' => 1, 'va' => null, 'vb' => null, 'versesA' => [], 'versesB' => [], 'nav' => null]);
        $t->assertTrue(str_contains($pick, 'name="book"'));
        $t->assertTrue(str_contains($pick, 'name="cap"'));

        $server = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'ejemplo.org';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/comparar/juan/3/rvr1909/onbv';
        $meta = Seo::build('comparar', ['book' => ['slug' => 'juan', 'name' => 'Juan'], 'chapter' => 3, 'va' => $va, 'vb' => $vb]);
        $_SERVER = $server;
        $t->assertTrue($meta['noindex']);
        $t->assertSame('http://ejemplo.org/comparar/juan/3/rvr1909/onbv', $meta['canonical']);
        $t->assertTrue(str_contains($meta['desc'], 'Juan 3'));
    });

    $t->run('buscar: cada resultado ofrece botón de contexto expandible', function () use ($t) {
        $html = (static function (array $v): string {
            extract($v);
            ob_start();
            include __DIR__ . '/../../app/Views/search.php';
            return ob_get_clean();
        })([
            'q' => 'amor',
            'version' => ['id' => 1, 'code' => 'rvr1909', 'name' => 'RVR1909'],
            'versions' => [['id' => 1, 'code' => 'rvr1909', 'name' => 'RVR1909']],
            'results' => [['book_name' => 'Juan', 'book_slug' => 'juan', 'chapter' => 3, 'verse' => 16, 'text' => 'amor test']],
        ]);
        $t->assertTrue(str_contains($html, 'class="ctx-btn"'));
        $t->assertTrue(str_contains($html, 'data-b="juan"'));
        $t->assertTrue(str_contains($html, 'aria-expanded="false"'));
    });
};
