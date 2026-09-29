<?php

/**
 * Utilidad admin para API.Bible (no importa nada — el consumo es por API).
 *
 * Uso:
 *   php scripts/apibible_list.php                       # biblias en español
 *   php scripts/apibible_list.php --lang=eng            # otro idioma
 *   php scripts/apibible_list.php --bible=<id>          # metadata + copyright
 *   php scripts/apibible_list.php --bible=<id> --probe=JHN.3  # capítulo de prueba
 *
 * Con el id elegido, setea versions.api_bible_id en config/versions.php
 * (o directo en la tabla) + license_status='approved' + active=1 y corre
 * seed.php — el lector empezará a servirla vía API.
 */

require __DIR__ . '/../bootstrap.php';

use Biblia\Bible\ApiBibleClient;

$options = getopt('', ['lang::', 'bible:', 'probe:']);
$client = new ApiBibleClient((string) ($_ENV['API_BIBLE_KEY'] ?? ''));

if (!empty($options['bible'])) {
    $b = $client->bible($options['bible']);
    printf("%s — %s\nAbbr: %s\nIdioma: %s\nCopyright: %s\n\n",
        $b['id'], $b['nameLocal'] ?? $b['name'],
        $b['abbreviationLocal'] ?? $b['abbreviation'] ?? '',
        $b['language']['name'] ?? '', $b['copyright'] ?? '');
    if (!empty($options['probe'])) {
        $res = $client->getFull("/bibles/{$options['bible']}/chapters/{$options['probe']}", [
            'content-type' => 'json', 'include-verse-numbers' => 'false',
        ]);
        $verses = Biblia\Bible\ApiBibleService::parseChapter($res['data']['content'] ?? []);
        echo "Probe {$options['probe']}: " . count($verses) . " versículos, fumsId=" . ($res['meta']['fumsId'] ?? 'n/a') . "\n";
        echo "  v1: " . mb_substr(strip_tags(reset($verses) ?: ''), 0, 90) . "\n";
    }
    exit(0);
}

$lang = $options['lang'] ?? 'spa';
foreach ($client->bibles($lang) as $b) {
    printf("%-28s %-42s %s\n", $b['id'], $b['nameLocal'] ?? $b['name'], $b['abbreviationLocal'] ?? '');
}
