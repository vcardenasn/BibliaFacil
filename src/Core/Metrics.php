<?php

namespace Biblia\Core;

use PDO;
use Throwable;

/**
 * Métricas privacy-first (EPIC 16).
 *
 * Reglas: nunca IPs, nunca texto libre del usuario (queries, notas),
 * sin cookies nuevas, sin fingerprinting persistente. El "día activo"
 * se deduplica con un hash IP+UA+día+salt que rota cada 24h.
 * Todo es tolerante a fallos: si falta la tabla, no rompe la página.
 */
final class Metrics
{
    /** Métricas permitidas vía beacon (evita basura en la tabla). */
    public const ALLOWED = [
        'pref', 'vswitch', 'ann', 'share', 'listen',
        'visit_n', 'read_s', 'perf', 'perf_c', 'game_win',
    ];

    public static function enabled(): bool
    {
        return env('FF_METRICS', '1') === '1';
    }

    /** Suma $n a (metric, dim, hoy). Silencioso ante errores. */
    public static function bump(string $metric, string $dim = '', int $n = 1): void
    {
        if (!self::enabled() || $n < 1) {
            return;
        }
        try {
            $pdo = Database::getPdo();
            $today = date('Y-m-d');
            $up = $pdo->prepare('UPDATE metrics_daily SET n = n + :n WHERE metric = :m AND dim = :dm AND d = :d');
            $up->execute(['n' => $n, 'm' => $metric, 'dm' => $dim, 'd' => $today]);
            if ($up->rowCount() === 0) {
                try {
                    $pdo->prepare('INSERT INTO metrics_daily (metric, dim, d, n) VALUES (:m, :dm, :d, :n)')
                        ->execute(['m' => $metric, 'dm' => $dim, 'd' => $today, 'n' => $n]);
                } catch (Throwable) {
                    $up->execute(['n' => $n, 'm' => $metric, 'dm' => $dim, 'd' => $today]);
                }
            }
        } catch (Throwable) {
            // sin tabla → sin métricas, nunca rompe
        }
    }

    /**
     * Registra el "día activo" del visitante: hash rotativo IP+UA+fecha+salt.
     * La IP solo se usa en memoria para construir el hash — nunca se guarda.
     */
    public static function session(): void
    {
        if (!self::enabled()) {
            return;
        }
        try {
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
            $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
            $h = hash('sha256', $ip . '|' . $ua . '|' . date('Y-m-d') . '|' . env('APP_NAME', 'bibliafacil'));
            $pdo = Database::getPdo();
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $sql = $driver === 'mysql'
                ? 'INSERT IGNORE INTO metrics_dau (d, h) VALUES (:d, :h)'
                : 'INSERT OR IGNORE INTO metrics_dau (d, h) VALUES (:d, :h)';
            $pdo->prepare($sql)->execute(['d' => date('Y-m-d'), 'h' => $h]);
        } catch (Throwable) {
        }
    }

    /** Valida y limpia eventos del beacon: solo whitelist, dims seguras, n tope. */
    public static function cleanEvents(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach (array_slice($raw, 0, 30) as $e) {
            $m = $e[0] ?? null;
            $dim = (string) ($e[1] ?? '');
            $n = (int) ($e[2] ?? 1);
            // dim: solo slug-safe — jamás texto libre del usuario
            if (!is_string($m) || !in_array($m, self::ALLOWED, true)
                || !preg_match('/^[a-z0-9_:.+-]{0,80}$/i', $dim)) {
                continue;
            }
            $out[] = [$m, $dim, max(1, min($n, 600))];
        }
        return $out;
    }

    /** Filas de metrics_daily de los últimos $days días (para dashboard). */
    public static function range(int $days): array
    {
        try {
            $q = Database::getPdo()->prepare(
                'SELECT metric, dim, d, n FROM metrics_daily
                 WHERE d >= :since ORDER BY d DESC, metric, n DESC'
            );
            $q->execute(['since' => date('Y-m-d', strtotime("-{$days} days"))]);
            return $q->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }

    /** DAU por día de los últimos $days días: [ ['d'=>…, 'n'=>…], … ] */
    public static function dau(int $days): array
    {
        try {
            $q = Database::getPdo()->prepare(
                'SELECT d, COUNT(*) AS n FROM metrics_dau WHERE d >= :since GROUP BY d ORDER BY d DESC'
            );
            $q->execute(['since' => date('Y-m-d', strtotime("-{$days} days"))]);
            return $q->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }
}
