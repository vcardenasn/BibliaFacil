<?php

namespace Biblia\Core;

use PDO;
use Throwable;

/**
 * Contador de visitas anónimo — una fila por métrica/día en `stats`.
 * Tolerante a fallos: si la tabla no existe (prod sin upgrade) devuelve
 * null y nunca rompe la página.
 */
final class Stats
{
    public static function visit(?string $snapshot): ?array
    {
        if ($snapshot !== null && preg_match('/^[1-9][0-9]{0,9}$/D', $snapshot)) {
            return [0, (int) $snapshot];
        }
        return self::bump('pv');
    }

    /**
     * Suma 1 a la métrica del día.
     * @return ?array{0:int,1:int} [hoy, total acumulado] o null si no hay tabla.
     */
    public static function bump(string $metric): ?array
    {
        try {
            $pdo = Database::getPdo();
            $today = date('Y-m-d');
            $up = $pdo->prepare('UPDATE stats SET n = n + 1 WHERE metric = :m AND d = :d');
            $up->execute(['m' => $metric, 'd' => $today]);
            if ($up->rowCount() === 0) {
                try {
                    $pdo->prepare('INSERT INTO stats (metric, d, n) VALUES (:m, :d, 1)')
                        ->execute(['m' => $metric, 'd' => $today]);
                } catch (Throwable) {
                    // Carrera: otro proceso insertó primero → reintentar el update.
                    $up->execute(['m' => $metric, 'd' => $today]);
                }
            }

            $q = $pdo->prepare('SELECT n FROM stats WHERE metric = :m AND d = :d');
            $q->execute(['m' => $metric, 'd' => $today]);
            $hoy = (int) $q->fetchColumn();

            $q = $pdo->prepare('SELECT COALESCE(SUM(n), 0) FROM stats WHERE metric = :m');
            $q->execute(['m' => $metric]);
            return [$hoy, (int) $q->fetchColumn()];
        } catch (Throwable) {
            return null;
        }
    }
}
