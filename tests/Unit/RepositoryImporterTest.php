<?php

use Biblia\Bible\BibleImporter;
use Biblia\Bible\BibleRepository;

// US-020/021/030 — repositorio + importador contra SQLite in-memory.

return function (TestCase $t): void {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $migration = require BASE_PATH . '/database/migrations/0001_create_biblia.php';
    $migration['up']($pdo);

    // seed mínimo
    $pdo->exec("INSERT INTO versions (code, name, language, license, license_status, active)
                VALUES ('rvr1909', 'Reina-Valera 1909', 'es', 'public_domain', 'open', 1),
                       ('kjv', 'King James Version', 'en', 'public_domain', 'open', 1),
                       ('nvi', 'Nueva Versión Internacional', 'es', 'copyrighted', 'requested', 0)");
    $books = require CONFIG_PATH . '/books.php';
    $insB = $pdo->prepare('INSERT INTO books (ord, osis, name, slug, aliases, testament, chapters) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($books as $b) {
        $insB->execute([$b['ord'], $b['osis'], $b['name'], $b['slug'], $b['aliases'], $b['testament'], $b['chapters']]);
    }

    // fixture de 2 libros
    $fixture = sys_get_temp_dir() . '/bf_fixture.json';
    file_put_contents($fixture, json_encode([
        'translation' => 'test',
        'books' => array_merge(
            [['name' => 'Genesis', 'chapters' => [
                ['chapter' => 1, 'verses' => [['verse' => 1, 'text' => 'En el principio'], ['verse' => 2, 'text' => 'Y la tierra']]],
                ['chapter' => 2, 'verses' => [['verse' => 1, 'text' => 'Fueron acabados los cielos']]],
            ]]],
            array_fill(1, 65, ['name' => 'x', 'chapters' => [['chapter' => 1, 'verses' => [['verse' => 1, 'text' => 'x']]]]]),
            [['name' => 'Revelation', 'chapters' => [['chapter' => 1, 'verses' => [['verse' => 1, 'text' => 'Apocalipsis test']]]]]]
        ),
    ]));

    $stats = (new BibleImporter($pdo))->importFile($fixture, 'rvr1909');
    $repo = new BibleRepository($pdo);

    $t->run('import: 68 versículos insertados', fn () => $t->assertSame(68, $stats['inserted']));
    $t->run('import: idempotente (segundo run = 0)', function () use ($pdo, $fixture, $t) {
        $s = (new BibleImporter($pdo))->importFile($fixture, 'rvr1909');
        $t->assertSame(0, $s['inserted']);
        $t->assertSame(68, $s['skipped']);
    });

    $t->run('versions(): solo activas con licencia abierta', function () use ($repo, $t) {
        $v = array_column($repo->versions(), 'code');
        $t->assertTrue(in_array('rvr1909', $v, true));
        $t->assertFalse(in_array('nvi', $v, true), 'nvi no debe aparecer (requested + inactive)');
    });

    $t->run('versionByCode', fn () => $t->assertSame('rvr1909', $repo->versionByCode('rvr1909')['code']));
    $t->run('books(): 66', fn () => $t->assertSame(66, count($repo->books())));
    $t->run('book() por slug/osis/ord', function () use ($repo, $t) {
        $t->assertSame('JHN', $repo->book('juan')['osis']);
        $t->assertSame('juan', $repo->book('JHN')['slug']);
        $t->assertSame('GEN', $repo->book('1')['osis']);
    });

    $t->run('chapter(): 2 versículos en Génesis 1', function () use ($repo, $pdo, $t) {
        $vid = (int) $pdo->query("SELECT id FROM versions WHERE code='rvr1909'")->fetchColumn();
        $bid = (int) $pdo->query("SELECT id FROM books WHERE osis='GEN'")->fetchColumn();
        $t->assertSame(2, count($repo->chapter($vid, $bid, 1)));
    });

    $t->run('chapterNav: dentro del libro', function () use ($repo, $t) {
        $book = $repo->book('genesis');
        $nav = $repo->chapterNav(['code' => 'rvr1909'], $book, 5);
        $t->assertSame('/rvr1909/genesis/4', $nav['prev']);
        $t->assertSame('/rvr1909/genesis/6', $nav['next']);
    });

    $t->run('chapterNav: límite de libro → Génesis 1 prev es null', function () use ($repo, $t) {
        $nav = $repo->chapterNav(['code' => 'rvr1909'], $repo->book('genesis'), 1);
        $t->assertNull($nav['prev']);
        $t->assertSame('/rvr1909/genesis/2', $nav['next']);
    });

    $t->run('chapterNav: Apocalipsis 22 next es null', function () use ($repo, $t) {
        $nav = $repo->chapterNav(['code' => 'rvr1909'], $repo->book('apocalipsis'), 22);
        $t->assertNull($nav['next']);
        $t->assertSame('/rvr1909/apocalipsis/21', $nav['prev']);
    });

    $t->run('chapterNav: cruce a libro anterior/siguiente', function () use ($repo, $t) {
        $nav = $repo->chapterNav(['code' => 'rvr1909'], $repo->book('exodo'), 1);
        $t->assertSame('/rvr1909/genesis/50', $nav['prev']);
        $nav2 = $repo->chapterNav(['code' => 'rvr1909'], $repo->book('genesis'), 50);
        $t->assertSame('/rvr1909/exodo/1', $nav2['next']);
    });

    $t->run('search: LIKE encuentra "principio"', function () use ($repo, $pdo, $t) {
        $vid = (int) $pdo->query("SELECT id FROM versions WHERE code='rvr1909'")->fetchColumn();
        $r = $repo->search($vid, 'principio');
        $t->assertSame(1, count($r));
        $t->assertSame('Génesis', $r[0]['book_name']);
    });

    $t->run('search: <3 chars → vacío', function () use ($repo, $pdo, $t) {
        $vid = (int) $pdo->query("SELECT id FROM versions WHERE code='rvr1909'")->fetchColumn();
        $t->assertSame([], $repo->search($vid, 'en'));
    });
};
