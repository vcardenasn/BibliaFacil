<?php
// DIAGNÓSTICO TEMPORAL — API.Bible. Subir al docroot, abrir en el navegador,
// copiar la salida y BORRAR el archivo. No imprime la clave completa.

header('Content-Type: text/plain; charset=utf-8');
require dirname(__DIR__) . '/bootstrap.php';

// Candado: solo corre con ?k=<primeros 8 del md5 de DB_PASS> — no queda
// abierto al público mientras esté subido.
$token = substr(md5((string) env('DB_PASS', 'x')), 0, 8);
if (($_GET['k'] ?? '') !== $token) {
    http_response_code(404);
    exit('Not found');
}
echo "Token: {$token} (úsalo como ?k={$token})\n\n";

echo "== Diagnóstico API.Bible ==\n\n";

// 1. ¿Se lee la clave del .env?
$envFile = BASE_PATH . '/.env';
echo "BASE_PATH: " . BASE_PATH . "\n";
echo ".env existe: " . (is_file($envFile) ? 'sí' : 'NO — ' . $envFile . "\n") . "\n";
$key = trim((string) ($_ENV['API_BIBLE_KEY'] ?? ''));
if ($key === '') {
    echo "API_BIBLE_KEY: NO se leyó (vacía o ausente)\n";
    if (is_file($envFile)) {
        $line = '';
        foreach (file($envFile) ?: [] as $l) {
            if (stripos($l, 'API_BIBLE_KEY') !== false) { $line = trim($l); }
        }
        echo "Línea en .env (clave tapada): " . ($line !== '' ? preg_replace('/=.{4}/', '=****', $line) : 'no hay ninguna línea API_BIBLE_KEY') . "\n";
    }
    echo "\n→ Sin clave el servicio devuelve null y la versión queda vacía.\n";
    exit;
}
echo "API_BIBLE_KEY: leída (" . strlen($key) . " chars, empieza " . substr($key, 0, 3) . "***)\n";

// 2. Versión en BD
try {
    $pdo = \Biblia\Core\Database::getPdo();
    $cols = $pdo->query("SHOW COLUMNS FROM versions LIKE 'api_bible_id'")->fetchAll();
    echo "Columna api_bible_id: " . ($cols ? 'existe' : 'NO EXISTE — corre upgrade_versions_api_bible.sql') . "\n";
    foreach ($pdo->query("SELECT code, license_status, active, api_bible_id FROM versions WHERE code IN ('ntv','nbla')") as $r) {
        echo "  {$r['code']}: license={$r['license_status']} active={$r['active']} api_id={$r['api_bible_id']}\n";
    }
} catch (Throwable $e) {
    echo "BD: " . $e->getMessage() . "\n";
}

// 3. Llamada real a API.Bible (capítulo JHN.3 en NTV)
$bibleId = '826f63861180e056-01';
$url = "https://api.scripture.api.bible/v1/bibles/{$bibleId}/chapters/JHN.3?content-type=json&include-notes=false";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['api-key: ' . $key],
    CURLOPT_TIMEOUT => 20,
]);
$body = curl_exec($ch);
$status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$errno = curl_errno($ch);
$err = curl_error($ch);
curl_close($ch);

echo "\n== Llamada NTV JHN.3 ==\n";
if ($errno) {
    echo "cURL error {$errno}: {$err}\n→ El hosting no logra salir a api.scripture.api.bible\n";
} else {
    echo "HTTP {$status}\n";
    if ($status === 200) {
        $data = json_decode((string) $body, true);
        $verses = \Biblia\Bible\ApiBibleService::parseChapter($data['data']['content'] ?? []);
        echo "Versículos parseados: " . count($verses) . "\n";
        echo "fumsId: " . (($data['meta']['fumsId'] ?? null) ?: '(sin fumsId)') . "\n";
    } else {
        echo "Respuesta: " . substr((string) $body, 0, 300) . "\n";
    }
}

// 4. Permisos de escritura de la caché
$dir = STORAGE_PATH . '/apibible';
echo "\nCache dir writable: " . (is_writable(STORAGE_PATH) ? 'sí' : 'NO — revisa permisos de storage/') . "\n";
echo "\nBorra este archivo cuando termines.\n";
