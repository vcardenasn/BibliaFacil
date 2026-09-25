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
            $pdo = new PDO('sqlite:' . ($env['DB_SQLITE_PATH'] ?? $base . '/storage/biblia.sqlite'));
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
