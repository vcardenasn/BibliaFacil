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
            ['pv_client', 'reader', 1],
            ['pv_client', 'unknown-route', 1],
            ['search_result_open', 'rvr1909', 1],
            ['search_result_open', 'private-slug', 1],
            ['plan_discover', 'reader', 1],
            ['plan_discover', 'private-slug', 1],
            ['plan_start', 'biblia-en-un-ano', 1],
            ['plan_day_done', 'salmos-proverbios', 1],
            ['plan_start', 'private-slug', 1],
            ['share_exposure', 'verse', 1],
            ['share_intent', 'topic:wa', 1],
            ['share_intent', 'reader:term=private', 1],
            ['share_native_return', 'reader:native', 1],
            ['share_native_return', 'reader:whatsapp', 1],
            ['game_start', 'david', 1],
            ['game_start', 'david:r1', 1],
            ['game_start', 'david:r5', 1],
            ['game_start', 'private-slug', 1],
            ['game_again', 'paloma', 1],
            ['game_round', 'david:fail', 1],
            ['game_round', 'david:r1:win', 1],
            ['game_round', 'david:r1:private', 1],
            ['game_round', 'david:private text', 1],
        ]);
        $t->assertSame([
            ['pref', 'theme:dark', 1], ['share', 'copy', 600],
            ['pv_client', 'reader', 1], ['search_result_open', 'rvr1909', 1],
            ['plan_discover', 'reader', 1], ['plan_start', 'biblia-en-un-ano', 1], ['plan_day_done', 'salmos-proverbios', 1],
            ['share_exposure', 'verse', 1], ['share_intent', 'topic:wa', 1],
            ['share_native_return', 'reader:native', 1], ['game_start', 'david', 1],
            ['game_start', 'david:r1', 1], ['game_again', 'paloma', 1],
            ['game_round', 'david:fail', 1], ['game_round', 'david:r1:win', 1],
        ], $ev);
    });

    $t->run('metrics: detecta crawlers sin guardar el User-Agent', function () use ($t) {
        $t->assertTrue(Metrics::isKnownCrawler('Googlebot/2.1 (+https://google.com/bot.html)'));
    });

    $t->run('metrics: no clasifica Chrome normal como crawler', function () use ($t) {
        $t->assertFalse(Metrics::isKnownCrawler('Mozilla/5.0 Chrome/130.0 Safari/537.36'));
    });

    $t->run('metrics: DNT y GPC optan fuera de métricas', function () use ($t) {
        $t->assertTrue(Metrics::isOptedOut('yes', '0'));
        $t->assertTrue(Metrics::isOptedOut('0', '1'));
    });

    $t->run('metrics: agrupa 404 de versión sin guardar el slug', function () use ($t) {
        $t->assertSame('version:missing_book', Metrics::notFoundDimension('rvr1909', 'missing_book', ['rvr1909', 'kjv']));
    });

    $t->run('metrics: colapsa 404 desconocidos y motivos inválidos', function () use ($t) {
        $t->assertSame('other:unknown', Metrics::notFoundDimension('private-slug', 'texto de búsqueda', ['rvr1909']));
    });

    $t->run('metrics: no cuenta crawlers como cliente-día', function () use ($t, $pdo) {
        $server = $_SERVER;
        $_SERVER['HTTP_USER_AGENT'] = 'Googlebot/2.1';
        $_SERVER['HTTP_DNT'] = '0';
        $_SERVER['HTTP_SEC_GPC'] = '0';
        Metrics::session();
        $_SERVER = $server;
        $t->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM metrics_dau')->fetchColumn());
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
