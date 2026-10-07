<?php

use Biblia\Api\V1\BibleReadService;
use Biblia\Bible\BibleRepository;

return function (TestCase $t): void {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    foreach (glob(BASE_PATH . '/database/migrations/*.php') as $migration) {
        (require $migration)['up']($pdo);
    }
    $pdo->exec("INSERT INTO versions (code, name, language, copyright, license, license_status, active, api_bible_id)
        VALUES ('rvr1909', 'Reina-Valera 1909', 'es', 'Dominio público', 'public_domain', 'open', 1, NULL),
               ('ntv', 'Nueva Traducción Viviente', 'es', '© Tyndale', 'copyrighted', 'approved', 1, 'bible-provider-id')");
    $pdo->exec("INSERT INTO books (ord, osis, name, slug, aliases, testament, chapters)
        VALUES (1, 'GEN', 'Génesis', 'genesis', 'genesis', 'AT', 50)");
    $pdo->exec("INSERT INTO verses (version_id, book_id, chapter, verse, text, wj)
        VALUES (1, 1, 1, 1, 'En el principio', '[[0,3]]')");
    $service = new BibleReadService(new BibleRepository($pdo));

    $t->run('api v1 catalog: expone solo versiones y campos públicos', function () use ($service, $t) {
        $t->assertSame([
            'versions' => [[
                'code' => 'rvr1909',
                'name' => 'Reina-Valera 1909',
                'language' => 'es',
                'license' => 'public_domain',
                'license_status' => 'open',
                'attribution' => 'Dominio público',
                'content_source' => 'local',
            ]],
            'books' => [[
                'slug' => 'genesis',
                'osis' => 'GEN',
                'name' => 'Génesis',
                'order' => 1,
                'testament' => 'AT',
                'chapters' => 50,
            ]],
        ], $service->catalog());
    });

    $t->run('api v1 chapter: normaliza versículos y rangos wj', function () use ($service, $t) {
        $t->assertSame([
            'version' => ['code' => 'rvr1909', 'name' => 'Reina-Valera 1909', 'language' => 'es'],
            'book' => ['slug' => 'genesis', 'osis' => 'GEN', 'name' => 'Génesis', 'testament' => 'AT'],
            'chapter' => 1,
            'content_source' => 'local',
            'attribution' => 'Dominio público',
            'verses' => [['number' => 1, 'text' => 'En el principio', 'wj' => [[0, 3]]]],
        ], $service->chapter('rvr1909', 'genesis', 1));
    });

    $t->run('api v1 chapter: referencias fuera de catálogo devuelven null', function () use ($service, $t) {
        $t->assertNull($service->chapter('rvr1909', 'genesis', 51));
    });

    $t->run('api v1 chapter: versiones API.Bible quedan excluidas', function () use ($service, $t) {
        $t->assertNull($service->chapter('ntv', 'genesis', 1));
    });
};
