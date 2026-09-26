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
};
