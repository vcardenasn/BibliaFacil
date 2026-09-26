<?php

// EPIC 08 — PWA: manifest íntegro, SW con estrategia correcta, .htaccess no lo
// deja atrapado en caché anual.

return function (TestCase $t): void {
    $t->run('manifest: JSON válido, campos instalables e íconos reales', function () use ($t) {
        $file = BASE_PATH . '/public/manifest.webmanifest';
        $m = json_decode((string) file_get_contents($file), true);
        $t->assertSame('Biblia Fácil', $m['name']);
        $t->assertSame('standalone', $m['display']);
        $t->assertSame('/', $m['start_url']);
        $t->assertSame('es', $m['lang']);
        $t->assertTrue(count($m['icons']) >= 3);
        foreach ($m['icons'] as $icon) {
            $t->assertTrue(
                is_file(BASE_PATH . '/public' . $icon['src']),
                'Falta ícono ' . $icon['src']
            );
        }
        $t->assertSame('maskable', $m['icons'][2]['purpose']);
    });

    $t->run('sw.js: network-first en páginas, nunca cachea api/track/POST', function () use ($t) {
        $sw = (string) file_get_contents(BASE_PATH . '/public/sw.js');
        $t->assertTrue(str_contains($sw, "req.method !== 'GET'"));
        $t->assertTrue(str_contains($sw, "mode === 'navigate'"));
        $t->assertTrue(str_contains($sw, 'track|check|diag'));
        $t->assertTrue(str_contains($sw, 'api'));
        $t->assertTrue(str_contains($sw, 'caches.match'));
        $t->assertTrue(str_contains($sw, 'MAX_PAGES'));
        // sin fingerprinting ni datos personales
        $t->assertFalse(str_contains($sw, 'localStorage'));
        $t->assertFalse(str_contains($sw, 'indexedDB'));
    });

    $t->run('.htaccess: sw.js y manifest exentos del cache anual', function () use ($t) {
        $ht = (string) file_get_contents(BASE_PATH . '/public/.htaccess');
        $t->assertTrue(str_contains($ht, 'manifest+json'));
        $t->assertTrue(str_contains($ht, 'sw\.js'));
        $t->assertTrue(str_contains($ht, 'no-cache'));
    });
};
