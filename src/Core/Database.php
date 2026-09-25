<?php

namespace Biblia\Core;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function setPdo(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function getPdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $cfg = config('database');
        // driver opcional: default mysql (formato .env canónico VCN)
        if (($cfg['driver'] ?? 'mysql') === 'sqlite') {
            $path = $cfg['sqlite_path'];
            if ($path !== ':memory:') {
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }
            self::$pdo = new PDO('sqlite:' . $path, null, null, self::options());
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['port'],
                $cfg['name'],
                $cfg['charset']
            );
            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], self::options());
        }
        return self::$pdo;
    }

    public static function driver(): string
    {
        return self::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    private static function options(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
}
