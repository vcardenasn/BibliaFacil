<?php

// EPIC 22 / US-228 — auditoría de contraste AA sobre los tokens de app.css.
// Parsea los bloques :root, [data-theme], [data-accent] y [data-theme][data-accent]
// y calcula los ratios WCAG de las parejas de color usadas por la UI.

return function (TestCase $t): void {
    $css = file_get_contents(__DIR__ . '/../../public/assets/app.css');
    $t->assertTrue($css !== false, 'No se pudo leer app.css.');

    $lum = function (string $hex): float {
        $h = ltrim($hex, '#');
        if (strlen($h) === 3) {
            $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        }
        $c = [];
        for ($i = 0; $i < 3; $i++) {
            $c[] = hexdec(substr($h, $i * 2, 2)) / 255;
        }
        foreach ($c as $k => $v) {
            $c[$k] = $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
        }
        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    };
    $ratio = function (string $a, string $b) use ($lum): float {
        $l1 = $lum($a);
        $l2 = $lum($b);
        if ($l1 < $l2) {
            [$l1, $l2] = [$l2, $l1];
        }
        return ($l1 + 0.05) / ($l2 + 0.05);
    };

    // --- Parseo de bloques CSS ------------------------------------------------
    $clean = preg_replace('#/\*.*?\*/#s', '', $css);
    $root = $themes = $accents = $themeAccent = [];
    preg_match_all('/([^{}]+)\{([^{}]*)\}/', $clean, $blocks, PREG_SET_ORDER);
    foreach ($blocks as $b) {
        $sel = trim(preg_replace('/\s+/', ' ', $b[1]));
        preg_match_all('/--([\w-]+)\s*:\s*(#[0-9a-fA-F]{3,8})\b/', $b[2], $vm, PREG_SET_ORDER);
        if (!$vm) {
            continue;
        }
        $vars = [];
        foreach ($vm as $v) {
            $vars[$v[1]] = strtolower($v[2]);
        }
        if ($sel === ':root') {
            $root = array_merge($root, $vars);
        } elseif (preg_match('/^\[data-theme="(\w+)"\]\[data-accent="(\w+)"\]$/', $sel, $sm)) {
            $themeAccent[$sm[1]][$sm[2]] = array_merge($themeAccent[$sm[1]][$sm[2]] ?? [], $vars);
        } elseif (preg_match('/^\[data-theme="(\w+)"\]$/', $sel, $sm)) {
            $themes[$sm[1]] = array_merge($themes[$sm[1]] ?? [], $vars);
        } elseif (preg_match('/^\[data-accent="(\w+)"\]$/', $sel, $sm)) {
            $accents[$sm[1]] = array_merge($accents[$sm[1]] ?? [], $vars);
        }
    }
    $t->assertTrue(isset($root['ink'], $root['surface']), 'No se parsearon tokens base.');

    preg_match('/\.wj\s*\{[^}]*#([0-9a-fA-F]{6})/', $css, $wjm);
    preg_match('/\[data-theme="dark"\]\s*\.wj\s*\{[^}]*#([0-9a-fA-F]{6})/', $css, $wjdm);
    $wjBase = '#' . strtolower($wjm[1] ?? '000000');
    $wjDark = '#' . strtolower($wjdm[1] ?? '000000');

    $resolve = function (string $theme, ?string $accent) use ($root, $themes, $accents, $themeAccent, $wjBase, $wjDark): array {
        $e = $root;
        if ($theme !== 'light') {
            $e = array_merge($e, $themes[$theme] ?? []);
        }
        if ($accent !== null) {
            $e = array_merge($e, $accents[$accent] ?? [], $themeAccent[$theme][$accent] ?? []);
        }
        $e['wj'] = $theme === 'dark' ? $wjDark : $wjBase;
        return $e;
    };

    $check = function (array $e, string $fg, string $bg, float $min, string $ctx) use ($t, $ratio): void {
        $t->assertTrue(
            isset($e[$fg], $e[$bg]),
            "{$ctx}: falta token {$fg} o {$bg}."
        );
        $r = round($ratio($e[$fg], $e[$bg]), 2);
        $t->assertTrue(
            $r >= $min,
            sprintf('%s: %s (%s) sobre %s (%s) = %.2f, mínimo %.1f', $ctx, $fg, $e[$fg], $bg, $e[$bg], $r, $min)
        );
    };

    $themeList = ['light', 'dark', 'sepia', 'contrast'];
    $accentList = ['indigo', 'oliva', 'terracota', 'purpura', 'teal'];

    // --- Matriz por tema (sin acento y con cada acento) ------------------------
    $t->run('contraste AA: matriz tema×acento (texto, marca, dorado, bordes, resaltados)', function () use ($t, $themeList, $accentList, $resolve, $check) {
        foreach ($themeList as $theme) {
        foreach (array_merge([null], $accentList) as $accent) {
            $e = $resolve($theme, $accent);
            $ctx = $theme . ($accent ? "+{$accent}" : '');
            // Texto y texto secundario
            $check($e, 'ink', 'bg', 4.5, $ctx);
            $check($e, 'ink', 'surface', 4.5, $ctx);
            $check($e, 'muted', 'bg', 4.5, $ctx);
            $check($e, 'muted', 'surface', 4.5, $ctx);
            $check($e, 'muted', 'brand-soft', 4.5, $ctx);
            // Enlaces, botones y estados de marca
            $check($e, 'brand', 'bg', 4.5, $ctx);
            $check($e, 'brand', 'surface', 4.5, $ctx);
            $check($e, 'brand', 'brand-soft', 4.5, $ctx);
            $check($e, 'brand', 'gold-soft', 4.5, $ctx);
            $check($e, 'brand-ink', 'brand', 4.5, $ctx);
            // Dorado: decorativo (>=3) y texto pequeño gold-ink (>=4.5)
            $check($e, 'gold', 'surface', 3.0, $ctx);
            $check($e, 'gold', 'bg', 3.0, $ctx);
            $check($e, 'gold-ink', 'surface', 4.5, $ctx);
            $check($e, 'gold-ink', 'gold-soft', 4.5, $ctx);
            $check($e, 'secondary', 'bg', 4.5, $ctx);
            // Palabras de Jesús sobre superficie y fondos suaves
            $check($e, 'wj', 'surface', 4.5, $ctx);
            $check($e, 'wj', 'gold-soft', 4.5, $ctx);
            // Bordes de controles interactivos (WCAG 1.4.11)
            $check($e, 'border-strong', 'bg', 3.0, $ctx);
            $check($e, 'border-strong', 'surface', 3.0, $ctx);
            // Resaltados con texto normal
            for ($i = 1; $i <= 5; $i++) {
                $check($e, 'ink', "hl{$i}", 4.5, "{$ctx} hl{$i}");
            }
            // Rojo de Jesús sobre resaltados claros (en oscuro se usa tinta)
            if ($theme !== 'dark') {
                for ($i = 1; $i <= 5; $i++) {
                    $check($e, 'wj', "hl{$i}", 4.5, "{$ctx} wj/hl{$i}");
                }
            }
        }
        }
    });

    // --- Paleta de juegos (juegos.css) -----------------------------------------
    $jcss = file_get_contents(__DIR__ . '/../../public/assets/juegos.css');
    $t->assertTrue($jcss !== false, 'No se pudo leer juegos.css.');

    $t->run('contraste AA: paleta de juegos y hero', function () use ($t, $jcss, $ratio, $resolve, $themeList) {
    preg_match('/\.jpage\s*\{([^}]*)\}/', $jcss, $jm);
    preg_match('/\[data-theme="dark"\]\s*\.jpage\s*\{([^}]*)\}/', $jcss, $jdm);
    $fun = $funDark = [];
    foreach ([$jm[1] ?? '', $jdm[1] ?? ''] as $i => $body) {
        preg_match_all('/--(fun\d)\s*:\s*(#[0-9a-fA-F]{3,8})/', $body, $fm, PREG_SET_ORDER);
        foreach ($fm as $v) {
            if ($i === 0) {
                $fun[$v[1]] = strtolower($v[2]);
            } else {
                $funDark[$v[1]] = strtolower($v[2]);
            }
        }
    }
    // Texto oscuro fijo en botones de feedback ok/bad
    preg_match('/\.jbtn\.ok\s*\{[^}]*color:\s*(#[0-9a-fA-F]{3,8})/', $jcss, $okm);
    preg_match('/\.jbtn\.bad\s*\{[^}]*color:\s*(#[0-9a-fA-F]{3,8})/', $jcss, $badm);
    $t->assertTrue(isset($okm[1], $badm[1]), 'No se parsearon colores de .jbtn.ok/.bad.');
    $r = $ratio($okm[1], $fun['fun3']);
    $t->assertTrue($r >= 4.5, sprintf('jbtn.ok texto sobre fun3 = %.2f', $r));
    $r = $ratio($badm[1], $fun['fun2']);
    $t->assertTrue($r >= 4.5, sprintf('jbtn.bad texto sobre fun2 = %.2f', $r));
    // Hero de juegos: texto brand-ink sobre ambos extremos del degradado
    foreach ($themeList as $theme) {
        $e = $resolve($theme, null);
        $f5 = $funDark['fun5'] ?? $fun['fun5'];
        $f5 = $theme === 'dark' ? $f5 : $fun['fun5'];
        if ($theme === 'contrast') {
            continue; // hero es sólido --brand (ya cubierto por brand-ink/brand)
        }
        $r = $ratio($e['brand-ink'], $f5);
        $t->assertTrue($r >= 4.5, sprintf('jh-hero %s brand-ink sobre fun5 = %.2f', $theme, $r));
    }
    });
};
