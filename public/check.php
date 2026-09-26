<?php

/**
 * Health check / verificación de permisos de BibliaFacil en el host.
 *
 * Web:   https://<host>/check.php?key=<HEALTHCHECK_TOKEN>[&fix=1]
 * CLI:   php public/check.php [--fix]
 *
 * Requiere HEALTHCHECK_TOKEN en .env para acceso web (si no está, solo CLI).
 * fix=1 corrige: directorios → 0755, archivos → 0644, crea dirs de runtime.
 */

$isCli = PHP_SAPI === 'cli';
$fix = $isCli ? in_array('--fix', $argv ?? [], true) : (($_GET['fix'] ?? '') === '1');

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    $envFile = dirname(__DIR__) . '/.env';
    $token = null;
    if (is_file($envFile)) {
        $env = parse_ini_file($envFile, false, INI_SCANNER_RAW) ?: [];
        $token = $env['HEALTHCHECK_TOKEN'] ?? null;
    }
    if ($token === null || $token === '' || !hash_equals($token, (string) ($_GET['key'] ?? ''))) {
        http_response_code(403);
        exit("Forbidden. Define HEALTHCHECK_TOKEN en .env y accede con ?key=<token>\n");
    }
}

$base = dirname(__DIR__);

// ---- Modo métricas: ?key=…&metrics=1 (HTML) | &csv=1 (CSV) -------------------
if (!$isCli && ((($_GET['metrics'] ?? '') === '1') || (($_GET['csv'] ?? '') === '1'))) {
    $env = parse_ini_file($base . '/.env', false, INI_SCANNER_RAW) ?: [];
    try {
        if (($env['DB_DRIVER'] ?? 'mysql') === 'sqlite') {
            $sp = (string) ($env['DB_SQLITE_PATH'] ?? $base . '/storage/biblia.sqlite');
            if ($sp !== '' && $sp[0] !== '/' && !preg_match('/^[A-Za-z]:[\\\\\\/]/', $sp)) {
                $sp = $base . '/' . $sp;
            }
            $mpdo = new PDO('sqlite:' . $sp);
        } else {
            $mpdo = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $env['DB_HOST'] ?? 'localhost', $env['DB_PORT'] ?? '3306', $env['DB_NAME'] ?? ''),
                $env['DB_USER'] ?? '', $env['DB_PASS'] ?? '', [PDO::ATTR_TIMEOUT => 5]
            );
        }
        $since = date('Y-m-d', strtotime('-30 days'));
        $rows = $mpdo->query("SELECT metric, dim, d, n FROM metrics_daily WHERE d >= '{$since}' ORDER BY d DESC, metric, n DESC")->fetchAll(PDO::FETCH_ASSOC);
        $dau = $mpdo->query("SELECT d, COUNT(*) AS n FROM metrics_dau WHERE d >= '{$since}' GROUP BY d ORDER BY d DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        http_response_code(500);
        exit("Sin acceso a métricas: " . $e->getMessage() . "\n(corre database/upgrade_metrics.sql en prod)");
    }

    if (($_GET['csv'] ?? '') === '1') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="metricas_30d.csv"');
        $f = fopen('php://output', 'w');
        fputcsv($f, ['fecha', 'metrica', 'dimension', 'n'], ',', '"', '');
        foreach ($rows as $r) {
            fputcsv($f, [$r['d'], $r['metric'], $r['dim'], $r['n']], ',', '"', '');
        }
        fclose($f);
        exit;
    }

    // Dashboard HTML — agrega últimos 7 y 30 días por métrica
    header('Content-Type: text/html; charset=utf-8');
    $d7 = date('Y-m-d', strtotime('-7 days'));
    $agg = ['7' => [], '30' => []];
    foreach ($rows as $r) {
        foreach ([7, 30] as $w) {
            if ($r['d'] >= date('Y-m-d', strtotime("-{$w} days"))) {
                $agg[$w][$r['metric']][$r['dim']] = ($agg[$w][$r['metric']][$r['dim']] ?? 0) + (int) $r['n'];
            }
        }
    }
    $dau7 = array_sum(array_map(fn ($r) => (int) $r['n'], array_filter($dau, fn ($r) => $r['d'] >= $d7)));
    $dau30 = array_sum(array_map(fn ($r) => (int) $r['n'], $dau));
    $NAMES = [
        'pv' => 'Páginas vistas por sección', 'ver' => 'Versión más usada', 'cap' => 'Capítulos más leídos',
        'search' => 'Búsquedas (solo conteo)', 'search_r' => 'Búsquedas con/sin resultados', 'goto' => '"Ir a" usado',
        'sheet' => 'Versículos tocados (sheet)', 'img' => 'Imágenes generadas por formato',
        'votd' => 'Versículo del día visto', 'nav' => 'Navegación anterior/siguiente',
        'game' => 'Juegos abiertos', 'game_win' => 'Rondas completadas', 'game_stars' => 'Estrellas ganadas por juego',
        'game_perfect' => 'Rondas perfectas', 'game_s' => 'Segundos jugando por ronda',
        'pref' => 'Cambios de preferencia', 'vswitch' => 'Cambios de versión',
        'ann' => 'Anotaciones/export', 'share' => 'Compartidos', 'listen' => 'Audio escuchado',
        'visit_n' => 'Visita Nº del usuario', 'read_s' => 'Segundos de lectura',
        'perf' => 'Tiempo de carga (ms total)', 'perf_c' => 'Muestras de carga',
    ];
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">',
        '<title>Métricas — BibliaFacil</title><style>',
        'body{font-family:system-ui,sans-serif;max-width:820px;margin:2rem auto;padding:0 1rem;color:#1e1b4b}',
        'h1{font-size:1.4rem}h2{font-size:1rem;margin:1.4rem 0 .4rem;color:#4f46e5}',
        'table{border-collapse:collapse;width:100%;font-size:.85rem}td,th{border:1px solid #ddd;padding:.3rem .5rem;text-align:left}',
        'th{background:#eef2ff}.kpis{display:flex;gap:1rem;flex-wrap:wrap}.kpi{background:#eef2ff;border-radius:10px;padding:.7rem 1.1rem}',
        '.kpi b{font-size:1.5rem;display:block}small{color:#666}</style></head><body>',
        '<h1>📊 Métricas — BibliaFacil <small>(anónimas, agregadas)</small></h1>',
        '<div class="kpis"><div class="kpi"><b>', $dau7, '</b>usuarios únicos · 7d</div>',
        '<div class="kpi"><b>', $dau30, '</b>usuarios únicos · 30d</div></div>';
    foreach ($NAMES as $m => $label) {
        $d30 = $agg[30][$m] ?? [];
        if (!$d30) {
            continue;
        }
        arsort($d30);
        echo '<h2>', htmlspecialchars($label), '</h2><table><tr><th>dimensión</th><th>7 días</th><th>30 días</th></tr>';
        foreach (array_slice($d30, 0, 15, true) as $dim => $n30) {
            $n7 = $agg[7][$m][$dim] ?? 0;
            echo '<tr><td>', htmlspecialchars($dim === '' ? '(total)' : $dim), '</td><td>', $n7, '</td><td>', $n30, '</td></tr>';
        }
        echo '</table>';
    }
    echo '<p><small>CSV: <a href="?key=', htmlspecialchars((string) $_GET['key']), '&csv=1">descargar 30 días</a>',
        ' · Sin IPs ni texto del usuario — solo contadores agregados.</small></p></body></html>';
    exit;
}

$errors = 0;
$warnings = 0;
$fixed = 0;

$out = function (string $status, string $msg) use (&$errors, &$warnings) {
    if ($status === 'ERROR') {
        $errors++;
    } elseif ($status === 'WARN') {
        $warnings++;
    }
    echo str_pad($status, 6), " | ", $msg, "\n";
};

echo "== BibliaFacil Health Check ==\n";
echo "Base: {$base}\nModo: " . ($isCli ? 'CLI' : 'WEB') . ($fix ? ' + FIX' : '') . "\n\n";

// ---- 1. Entorno PHP --------------------------------------------------------
$out(PHP_VERSION_ID >= 80100 ? 'OK' : 'ERROR', 'PHP ' . PHP_VERSION . ' (requiere >= 8.1)');
foreach (['pdo_mysql', 'json', 'mbstring'] as $ext) {
    $out(extension_loaded($ext) ? 'OK' : 'ERROR', "ext-{$ext}");
}

// ---- 2. Archivos críticos --------------------------------------------------
foreach (['bootstrap.php', 'public/index.php', 'public/.htaccess', 'public/assets/app.css', 'public/assets/app.js', 'src/Core/Database.php', 'src/Bible/BibleRepository.php'] as $f) {
    $path = $base . '/' . $f;
    if (!is_file($path)) {
        $out('ERROR', "Falta {$f}");
        continue;
    }
    $size = filesize($path);
    $out($size > 0 && is_readable($path) ? 'OK' : 'ERROR', "{$f} ({$size} bytes)");
}

// ---- 3. .env ----------------------------------------------------------------
$envPath = $base . '/.env';
if (!is_file($envPath)) {
    $out('WARN', '.env no existe (se usan defaults; los flags quedan apagados)');
} else {
    $out('OK', '.env existe (' . filesize($envPath) . ' bytes)');
    $env = parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: [];
    foreach (['DB_HOST', 'DB_NAME', 'DB_USER'] as $k) {
        $out(!empty($env[$k]) ? 'OK' : 'WARN', ".env {$k}" . (empty($env[$k]) ? ' vacío' : ''));
    }
    $out(($env['FF_SEARCH'] ?? '0') === '1' ? 'OK' : 'WARN', '.env FF_SEARCH=' . ($env['FF_SEARCH'] ?? 'no definido'));
}

// ---- 4. Permisos (recursivo) -------------------------------------------------
echo "\n-- Permisos --\n";
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
$badDirs = 0;
$badFiles = 0;
foreach ($iterator as $item) {
    $path = $item->getPathname();
    $rel = substr($path, strlen($base) + 1);
    if (str_starts_with($rel, '.git') || str_starts_with($rel, 'vendor')) {
        continue;
    }
    $perms = substr(sprintf('%o', fileperms($path)), -3);
    if ($item->isDir()) {
        if ($perms !== '755') {
            $badDirs++;
            $out('WARN', "dir {$rel} = {$perms} (esperado 755)");
            if ($fix && @chmod($path, 0755)) {
                $fixed++;
                echo "FIX    | {$rel} → 755\n";
            }
        }
    } elseif ($perms !== '644' && $perms !== '444') {
        $badFiles++;
        $out('WARN', "file {$rel} = {$perms} (esperado 644)");
        if ($fix && @chmod($path, 0644)) {
            $fixed++;
            echo "FIX    | {$rel} → 644\n";
        }
    }
}
if ($badDirs === 0 && $badFiles === 0) {
    $out('OK', 'Todos los permisos correctos (755/644)');
}

// ---- 5. Dirs de runtime escribibles ------------------------------------------
echo "\n-- Runtime --\n";
foreach (['cache', 'logs', 'storage'] as $d) {
    $path = $base . '/' . $d;
    if (!is_dir($path)) {
        $out('WARN', "{$d}/ no existe");
        if ($fix && @mkdir($path, 0755, true)) {
            $fixed++;
            echo "FIX    | creado {$d}/\n";
        }
        continue;
    }
    $out(is_writable($path) ? 'OK' : 'ERROR', "{$d}/ " . (is_writable($path) ? 'escribible' : 'NO escribible'));
}

// ---- 6. Conexión BD + contenido ------------------------------------------------
echo "\n-- Base de datos --\n";
if (!is_file($envPath)) {
    $out('WARN', 'Sin .env — no se puede probar conexión');
} else {
    $env = parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: [];
    try {
        if (($env['DB_DRIVER'] ?? 'mysql') === 'sqlite') {
            $sp = (string) ($env['DB_SQLITE_PATH'] ?? $base . '/storage/biblia.sqlite');
            if ($sp !== '' && $sp[0] !== '/' && !preg_match('/^[A-Za-z]:[\\\\\\/]/', $sp)) {
                $sp = $base . '/' . $sp;
            }
            $pdo = new PDO('sqlite:' . $sp);
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $env['DB_HOST'] ?? 'localhost',
                $env['DB_PORT'] ?? '3306',
                $env['DB_NAME'] ?? ''
            );
            $pdo = new PDO($dsn, $env['DB_USER'] ?? '', $env['DB_PASS'] ?? '', [PDO::ATTR_TIMEOUT => 5]);
        }
        $out('OK', 'Conexión a BD exitosa');
        $tables = $pdo->query(
            $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
                ? "SELECT name FROM sqlite_master WHERE type='table'"
                : 'SHOW TABLES'
        )->fetchAll(PDO::FETCH_COLUMN);
        $out(count($tables) >= 4 ? 'OK' : 'WARN', count($tables) . ' tablas (esperadas ≥4 — correr migrate.php)');
        $books = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
        $out($books === 66 ? 'OK' : 'WARN', "{$books} libros (esperados 66 — correr seeds/seed.php)");
        foreach ($pdo->query('SELECT code, COUNT(v.id) AS t FROM versions s LEFT JOIN verses v ON v.version_id = s.id GROUP BY s.id') as $row) {
            $out((int) $row['t'] > 30000 ? 'OK' : 'WARN', "versión {$row['code']}: {$row['t']} versículos" . ((int) $row['t'] > 30000 ? '' : ' — importar'));
        }
    } catch (Throwable $e) {
        $out('ERROR', 'Conexión/consulta falló: ' . $e->getMessage());
    }
}

echo "\n== Resumen: {$errors} errores, {$warnings} avisos" . ($fix ? ", {$fixed} corregidos" : '') . " ==\n";
if (!$fix && ($badDirs > 0 || $badFiles > 0)) {
    echo "Tip: corre de nuevo con " . ($isCli ? '--fix' : '&fix=1') . " para corregir permisos.\n";
}
exit($errors > 0 ? 1 : 0);
