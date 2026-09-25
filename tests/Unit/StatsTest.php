<?php

use Biblia\Core\Database;
use Biblia\Core\Stats;

// EPIC 16 — contador de visitas contra SQLite in-memory.

return function (TestCase $t): void {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE stats (metric VARCHAR(40) NOT NULL, d DATE NOT NULL, n INT NOT NULL DEFAULT 0, PRIMARY KEY (metric, d))');
    Database::setPdo($pdo);

    $t->run('stats: primer bump → [1,1]', function () use ($t) {
        $t->assertSame([1, 1], Stats::bump('pv'));
    });

    $t->run('stats: bumps acumulan hoy y total', function () use ($t) {
        Stats::bump('pv');
        Stats::bump('pv');
        $t->assertSame([4, 4], Stats::bump('pv')); // 4º bump acumulado
    });

    $t->run('stats: métricas independientes', function () use ($t) {
        $t->assertSame([1, 1], Stats::bump('search'));
        $t->assertSame([5, 5], Stats::bump('pv'));
    });

    $t->run('stats: tabla inexistente → null sin romper', function () use ($t) {
        $bad = new PDO('sqlite::memory:');
        $bad->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        Database::setPdo($bad);
        $t->assertNull(Stats::bump('pv'));
    });

    Database::setPdo(null);
};
