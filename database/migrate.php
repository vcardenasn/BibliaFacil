<?php

require __DIR__ . '/../bootstrap.php';

use Biblia\Core\Database;

$pdo = Database::getPdo();
$mysql = Database::driver() === 'mysql';
$pk = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';

$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id {$pk},
        name VARCHAR(255) NOT NULL UNIQUE,
        batch INT NOT NULL,
        executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$action = $argv[1] ?? 'migrate';
$files = glob(__DIR__ . '/migrations/*.php');
usort($files, 'strnatcasecmp');

$run = function (PDO $pdo, callable|string $migration): void {
    is_callable($migration) ? $migration($pdo) : $pdo->exec($migration);
};

if ($action === 'migrate') {
    $executed = $pdo->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
    $batch = (int) $pdo->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations')->fetchColumn();

    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $executed, true)) {
            continue;
        }
        $migration = require $file;
        if (!is_array($migration) || !isset($migration['up'])) {
            throw new Exception("Invalid migration: {$name}");
        }
        $run($pdo, $migration['up']);
        $stmt = $pdo->prepare('INSERT INTO migrations (name, batch) VALUES (:name, :batch)');
        $stmt->execute(['name' => $name, 'batch' => $batch]);
        echo "Migrated: {$name}\n";
    }
    echo "Done.\n";
} elseif ($action === 'rollback') {
    $lastBatch = (int) $pdo->query('SELECT COALESCE(MAX(batch), 0) FROM migrations')->fetchColumn();
    if ($lastBatch === 0) {
        echo "Nothing to rollback.\n";
        exit(0);
    }
    $executed = $pdo->prepare('SELECT name FROM migrations WHERE batch = :batch ORDER BY name DESC');
    $executed->execute(['batch' => $lastBatch]);

    foreach ($executed->fetchAll(PDO::FETCH_COLUMN) as $name) {
        $path = __DIR__ . '/migrations/' . $name;
        if (!is_file($path)) {
            continue;
        }
        $migration = require $path;
        if (isset($migration['down'])) {
            $run($pdo, $migration['down']);
        }
        $pdo->prepare('DELETE FROM migrations WHERE name = :name')->execute(['name' => $name]);
        echo "Rolled back: {$name}\n";
    }
    echo "Done.\n";
} else {
    echo "Usage: php database/migrate.php [migrate|rollback]\n";
    exit(1);
}
