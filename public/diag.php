<?php

/**
 * Diagnóstico standalone del hosting — NO depende de bootstrap/.env.
 * Pensado para el setup inicial cuando algo falla (404, docroot, permisos).
 *
 * Web: https://<host>/diag.php[?fix=1]
 * CLI: php public/diag.php [--fix]
 *
 * fix corrige: dirs → 0755, archivos → 0644, crea cache/logs/storage.
 * BORRAR este archivo al terminar el setup (expone rutas del servidor).
 */

$isCli = PHP_SAPI === 'cli';
$fix = $isCli ? in_array('--fix', $argv ?? [], true) : (($_GET['fix'] ?? '') === '1');
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
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

echo "== BibliaFacil Diag (standalone) ==\n";
echo "Hora: " . date('Y-m-d H:i:s') . ($fix ? "  [FIX ON]" : '') . "\n\n";

// ---- 0. Docroot — la causa #1 de 404 Apache --------------------------------
echo "-- Docroot / rutas --\n";
$docroot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? '(cli)'), '/');
$out('INFO', "DOCUMENT_ROOT = {$docroot}");
$out('INFO', '__FILE__       = ' . __FILE__);
$out('INFO', 'App base       = ' . $base);
if (!$isCli) {
    $ok = str_ends_with($docroot, '/public') || str_ends_with($docroot, 'public');
    $out($ok ? 'OK' : 'ERROR', $ok
        ? 'El docroot apunta a public/ — correcto'
        : 'El docroot NO apunta a public/ — en cPanel pon Document Root = <dir-app>/public');
}

// ---- 1. PHP -----------------------------------------------------------------
echo "\n-- PHP --\n";
$out(PHP_VERSION_ID >= 80100 ? 'OK' : 'ERROR', 'PHP ' . PHP_VERSION . ' (requiere >= 8.1)');
foreach (['pdo_mysql', 'json', 'mbstring'] as $ext) {
    $out(extension_loaded($ext) ? 'OK' : 'ERROR', "ext-{$ext}");
}
$out('INFO', 'mod_rewrite: ' . (function_exists('apache_get_modules')
    ? (in_array('mod_rewrite', apache_get_modules(), true) ? 'ON' : 'OFF')
    : 'no detectable — probar URL limpia /rvr1909/genesis/1'));

// ---- 2. Archivos desplegados -------------------------------------------------
echo "\n-- Archivos (relativo a {$base}) --\n";
foreach ([
    'bootstrap.php', '.env', 'public/index.php', 'public/check.php',
    'public/.htaccess', 'public/assets/app.css', 'public/assets/app.js',
    'config/app.php', 'config/database.php', 'config/books.php',
    'src/Core/Database.php', 'src/Bible/BibleRepository.php',
    'database/schema.sql',
] as $f) {
    $path = $base . '/' . $f;
    if (!is_file($path)) {
        $out($f === '.env' ? 'WARN' : 'ERROR', "Falta {$f}" . ($f === '.env' ? ' (crearlo a mano en el host)' : ' — ¿deploy incompleto o FTP_DIR incorrecto?'));
        continue;
    }
    $out('OK', "{$f} (" . filesize($path) . ' b)');
}

// ---- 3. Permisos recursivos ---------------------------------------------------
echo "\n-- Permisos (esperado: dirs 755 / files 644) --\n";
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
$bad = 0;
foreach ($iterator as $item) {
    $path = $item->getPathname();
    $rel = substr($path, strlen($base) + 1);
    if (str_starts_with($rel, '.git') || str_starts_with($rel, 'vendor')) {
        continue;
    }
    $perms = substr(sprintf('%o', fileperms($path)), -3);
    $expected = $item->isDir() ? '755' : '644';
    if ($perms !== $expected && $perms !== '444') {
        $bad++;
        $out('WARN', "{$rel} = {$perms} (esperado {$expected})");
        if ($fix && @chmod($path, (int) octdec('0' . $expected))) {
            $fixed++;
            echo "FIX    | {$rel} → {$expected}\n";
        }
    }
}
$out($bad === 0 ? 'OK' : 'WARN', $bad === 0 ? 'Permisos correctos' : "{$bad} archivo(s)/dir(s) con permisos distintos");

// ---- 4. Runtime escribible ----------------------------------------------------
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

// ---- 5. .env (solo presencia de claves, nunca valores) ------------------------
echo "\n-- .env --\n";
$envPath = $base . '/.env';
if (!is_file($envPath)) {
    $out('WARN', '.env no existe — créalo en la raíz de la app (ver .env.example)');
} else {
    $env = parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: [];
    $out('OK', '.env existe (' . count($env) . ' claves)');
    foreach (['APP_ENV', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'HEALTHCHECK_TOKEN', 'FF_SEARCH'] as $k) {
        $out(isset($env[$k]) && $env[$k] !== '' ? 'OK' : 'WARN', "{$k}: " . (isset($env[$k]) && $env[$k] !== '' ? 'definido' : 'falta/vacío'));
    }
    $out('INFO', 'APP_ENV=' . ($env['APP_ENV'] ?? 'n/d'));
}

// ---- 6. BD (opcional, solo si .env completo) ----------------------------------
echo "\n-- Base de datos --\n";
$env = is_file($envPath) ? (parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: []) : [];
if (empty($env['DB_NAME']) || empty($env['DB_USER'])) {
    $out('WARN', 'Sin credenciales — se omite prueba de conexión');
} else {
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $env['DB_HOST'] ?? 'localhost', $env['DB_PORT'] ?? '3306', $env['DB_NAME']);
        $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '', [PDO::ATTR_TIMEOUT => 5]);
        $out('OK', 'Conexión MySQL');
        $out((int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn() === 66 ? 'OK' : 'WARN',
            'books = ' . $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn() . ' (esperado 66 → importar schema.sql)');
        foreach ($pdo->query('SELECT v.code, COUNT(s.id) t FROM versions v LEFT JOIN verses s ON s.version_id=v.id GROUP BY v.id') as $r) {
            $out((int) $r['t'] > 0 ? 'OK' : 'WARN', "{$r['code']}: {$r['t']} versículos" . ((int) $r['t'] > 0 ? '' : ' → importar verses_' . $r['code'] . '.sql'));
        }
    } catch (Throwable $e) {
        $out('ERROR', 'BD: ' . $e->getMessage());
    }
}

echo "\n== Resumen: {$errors} errores, {$warnings} avisos" . ($fix ? ", {$fixed} corregidos" : '') . " ==\n";
echo "\n⚠ BORRA public/diag.php cuando termines el setup.\n";
exit($errors > 0 ? 1 : 0);
