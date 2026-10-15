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
        'visit_n', 'read_s', 'perf', 'perf_c', 'perf_b', 'pv_client',
        'search_result_open', 'plan_discover', 'plan_start', 'plan_day_done',
        'share_exposure', 'share_intent', 'share_native_return',
        'game_win', 'game_round', 'game_start', 'game_again', 'game_stars', 'game_perfect', 'game_s',
        'sheet', 'img', 'votd', 'nav', 'geo', 'lang',
    ];

    private const PAGE_VIEWS = [
        'notfound', 'search', 'temas', 'tema', 'versiculo', 'votd', 'guias', 'guia',
        'shareverse', 'comparar', 'planes', 'plan', 'licencias', 'mias', 'juegos',
        'juego', 'home', 'books', 'chapters', 'reader', 'error',
    ];
    private const GAME_EVENT_METRICS = ['game_win', 'game_start', 'game_again', 'game_stars', 'game_perfect', 'game_s'];
    private const SHARE_SOURCES = ['verse', 'votd', 'topic', 'reader', 'other'];
    private const SHARE_CHANNELS = ['wa', 'tg', 'x', 'fb', 'copy', 'copy_range', 'whatsapp', 'native', 'native_image', 'download_image'];
    private const NOT_FOUND_ROUTES = [
        'ir', 'buscar', 'comparar', 'planes', 'temas', 'versiculo', 'versiculo-del-dia',
        'guias', 'juegos', 'mias', 'v', 'api', 'sitemap', 'sitemap.xml',
    ];
    private const NOT_FOUND_REASONS = [
        'invalid_reference', 'missing_topic', 'missing_verse_page', 'missing_guide', 'missing_verse',
        'invalid_comparison', 'same_version', 'invalid_chapter', 'missing_versions', 'missing_plan',
        'unavailable_game', 'unavailable_version', 'missing_book', 'invalid_bible_resource',
        'invalid_short_reference', 'unknown_route',
    ];

    public static function enabled(): bool
    {
        return env('FF_METRICS', '1') === '1' && !self::isOptedOut();
    }

    public static function isOptedOut(?string $dnt = null, ?string $gpc = null): bool
    {
        $dnt = strtolower(trim($dnt ?? (string) ($_SERVER['HTTP_DNT'] ?? '')));
        $gpc = trim($gpc ?? (string) ($_SERVER['HTTP_SEC_GPC'] ?? ''));
        return in_array($dnt, ['1', 'yes'], true) || $gpc === '1';
    }

    public static function isKnownCrawler(string $userAgent): bool
    {
        return preg_match('/(?:bot\\b|crawler|spider|slurp|facebookexternalhit|facebot|twitterbot|linkedinbot|discordbot|telegrambot|whatsapp|applebot|amazonbot|bytespider|petalbot|google-inspectiontool)/i', $userAgent) === 1;
    }

    public static function notFoundDimension(string $segment, string $reason, array $versionCodes): string
    {
        $group = in_array($segment, $versionCodes, true)
            ? 'version'
            : (in_array($segment, self::NOT_FOUND_ROUTES, true) ? $segment : 'other');
        $reason = in_array($reason, self::NOT_FOUND_REASONS, true) ? $reason : 'unknown';
        return $group . ':' . $reason;
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
            if (self::isKnownCrawler($ua)) {
                return;
            }
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
            if ($m === 'pv_client' && !in_array($dim, self::PAGE_VIEWS, true)) {
                continue;
            }
            if ($m === 'search_result_open' && !in_array($dim, array_column(config('versions'), 'code'), true)) {
                continue;
            }
            if ($m === 'plan_discover' && !in_array($dim, ['reader', 'votd'], true)) {
                continue;
            }
            if (in_array($m, ['plan_start', 'plan_day_done'], true) && !isset(config('plans')[$dim])) {
                continue;
            }
            if ($m === 'share_exposure' && !in_array($dim, self::SHARE_SOURCES, true)) {
                continue;
            }
            if (in_array($m, ['share_intent', 'share_native_return'], true)) {
                [$source, $channel] = array_pad(explode(':', $dim, 2), 2, '');
                if (!in_array($source, self::SHARE_SOURCES, true) || !in_array($channel, self::SHARE_CHANNELS, true)
                    || ($m === 'share_native_return' && !in_array($channel, ['native', 'native_image'], true))) {
                    continue;
                }
            }
            if ($m === 'game_round' || in_array($m, self::GAME_EVENT_METRICS, true)) {
                $games = config('games');
                if ($m === 'game_round') {
                    [$slug, $outcome] = array_pad(explode(':', $dim, 2), 2, '');
                    if (!isset($games[$slug]) || !in_array($outcome, ['win', 'fail', 'complete'], true)) {
                        continue;
                    }
                } elseif (!isset($games[$dim])) {
                    continue;
                }
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
