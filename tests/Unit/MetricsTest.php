<?php

use Biblia\Core\Database;
use Biblia\Core\Metrics;

// EPIC 16 — métricas privacy-first contra SQLite in-memory.

return function (TestCase $t): void {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE metrics_daily (metric VARCHAR(40) NOT NULL, dim VARCHAR(120) NOT NULL DEFAULT "", d DATE NOT NULL, n INT NOT NULL DEFAULT 0, PRIMARY KEY (metric, dim, d))');
    $pdo->exec('CREATE TABLE metrics_dau (d DATE NOT NULL, h CHAR(64) NOT NULL, PRIMARY KEY (d, h))');
    Database::setPdo($pdo);

    $t->run('metrics: bump crea fila y acumula por dim', function () use ($t, $pdo) {
        Metrics::bump('pv', 'reader');
        Metrics::bump('pv', 'reader');
        Metrics::bump('pv', 'search');
        $t->assertSame(2, (int) $pdo->query("SELECT n FROM metrics_daily WHERE metric='pv' AND dim='reader'")->fetchColumn());
        $t->assertSame(1, (int) $pdo->query("SELECT n FROM metrics_daily WHERE metric='pv' AND dim='search'")->fetchColumn());
    });

    $t->run('metrics: bump con n>1 suma (read_s)', function () use ($t, $pdo) {
        Metrics::bump('read_s', 'cap', 45);
        Metrics::bump('read_s', 'cap', 15);
        $t->assertSame(60, (int) $pdo->query("SELECT n FROM metrics_daily WHERE metric='read_s' AND dim='cap'")->fetchColumn());
    });

    $t->run('metrics: cleanEvents filtra whitelist y dims libres', function () use ($t) {
        $ev = Metrics::cleanEvents([
            ['pref', 'theme:dark', 1],           // ok
            ['hack', 'x', 1],                    // métrica no permitida
            ['pref', 'q=texto libre', 1],        // dim con espacios/= → fuera
            ['share', 'copy', 99999],            // n se capea
        ]);
        $t->assertSame([['pref', 'theme:dark', 1], ['share', 'copy', 600]], $ev);
    });

    $t->run('metrics: session deduplica el mismo hash por día', function () use ($t, $pdo) {
        Metrics::session();
        Metrics::session(); // mismo IP+UA+día → mismo hash, no duplica
        $t->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM metrics_dau')->fetchColumn());
        $t->assertSame(64, strlen((string) $pdo->query('SELECT h FROM metrics_dau')->fetchColumn()));
    });

    $t->run('metrics: tabla inexistente → no rompe', function () use ($t) {
        Database::setPdo(new PDO('sqlite::memory:'));
        Metrics::bump('pv', 'x'); // no debe lanzar
        Metrics::session();
        $t->assertSame([], Metrics::range(7));
        $t->assertSame([], Metrics::dau(7));
    });

    Database::setPdo(null);
};
