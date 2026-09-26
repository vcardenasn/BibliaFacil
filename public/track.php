<?php

/**
 * Beacon de métricas (EPIC 16) — POST JSON {"e": [[metric, dim, n], …]}
 *
 * Privacidad: sin cookies, sin logging, nunca guarda IP ni texto libre.
 * Solo acepta métricas de la whitelist y dims slug-safe (Metrics::cleanEvents).
 * Apagar con FF_METRICS=0. Responde siempre 204.
 */

require __DIR__ . '/../bootstrap.php';

use Biblia\Core\Metrics;

http_response_code(204);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !Metrics::enabled()) {
    exit;
}

$body = json_decode((string) file_get_contents('php://input'), true);
foreach (Metrics::cleanEvents($body['e'] ?? null) as [$m, $dim, $n]) {
    Metrics::bump($m, $dim, $n);
}
exit;
