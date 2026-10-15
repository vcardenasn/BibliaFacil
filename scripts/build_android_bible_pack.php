<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$targetDir = $argv[1] ?? '';
if (!is_dir($targetDir) || !is_writable($targetDir)) {
    fwrite(STDERR, "Uso: php scripts/build_android_bible_pack.php <directorio-res/raw>\n");
    exit(1);
}

$booksCatalog = require CONFIG_PATH . '/books.php';
$versionsCatalog = array_column(require CONFIG_PATH . '/versions.php', null, 'code');
$chapterTotal = array_sum(array_column($booksCatalog, 'chapters'));

foreach (['pddpt', 'rvr1909'] as $position => $code) {
    $version = $versionsCatalog[$code] ?? throw new RuntimeException("Falta metadata para {$code}");
    if (!\Biblia\Api\V1\OfflineLicensePolicy::allows((string) $version['license'])) {
        throw new RuntimeException("La licencia de {$code} no permite el pack offline");
    }
    $sourcePath = BASE_PATH . "/database/sources/{$code}.json";
    $source = json_decode((string) file_get_contents($sourcePath), true, 512, JSON_THROW_ON_ERROR);
    $sourceBooks = array_slice($source['books'] ?? [], 0, 66);
    if (count($sourceBooks) !== 66) {
        throw new RuntimeException("Fuente incompleta para {$code}");
    }

    $chapters = [];
    foreach ($booksCatalog as $bookIndex => $book) {
        $sourceBook = $sourceBooks[$bookIndex];
        if (isset($sourceBook['osis']) && $sourceBook['osis'] !== $book['osis']) {
            throw new RuntimeException("Orden canónico incorrecto en {$code}: {$book['osis']}");
        }
        $sourceChapters = $sourceBook['chapters'] ?? [];
        if (count($sourceChapters) !== (int) $book['chapters']) {
            throw new RuntimeException("Capítulos incompletos en {$code}: {$book['osis']}");
        }
        foreach ($sourceChapters as $chapterIndex => $chapterSource) {
            $sourceVerses = isset($chapterSource['verses']) ? $chapterSource['verses'] : $chapterSource;
            $verses = [];
            foreach ($sourceVerses as $verseIndex => $verseSource) {
                $rawText = is_array($verseSource)
                    ? (string) ($verseSource['t'] ?? $verseSource['text'] ?? '')
                    : (string) $verseSource;
                [$text, $wjJson] = \Biblia\Bible\VerseText::split($rawText);
                if ($text === '') {
                    continue;
                }
                $verses[] = [
                    'number' => is_array($verseSource)
                        ? (int) ($verseSource['v'] ?? $verseSource['verse'] ?? $verseIndex + 1)
                        : $verseIndex + 1,
                    'text' => $text,
                    'wj' => $wjJson === null ? null : json_decode($wjJson, true, 512, JSON_THROW_ON_ERROR),
                ];
            }
            if ($verses === []) {
                throw new RuntimeException("Capítulo vacío en {$code}: {$book['osis']} " . ($chapterIndex + 1));
            }
            $chapters[] = [
                'bookSlug' => $book['slug'],
                'bookName' => $book['name'],
                'chapterNumber' => isset($chapterSource['chapter']) ? (int) $chapterSource['chapter'] : $chapterIndex + 1,
                'attribution' => $version['copyright'],
                'verses' => $verses,
            ];
        }
    }
    if (count($chapters) !== $chapterTotal) {
        throw new RuntimeException("Conteo de capítulos incorrecto para {$code}");
    }

    $pack = [
        'version' => [
            'code' => $code,
            'name' => $version['name'],
            'language' => $version['language'],
            'license' => $version['license'],
            'licenseStatus' => $version['license_status'],
            'attribution' => $version['copyright'],
            'offlineDownloadAllowed' => true,
            'position' => $position,
        ],
        'books' => array_map(static fn (array $book): array => [
            'slug' => $book['slug'],
            'osis' => $book['osis'],
            'name' => $book['name'],
            'testament' => $book['testament'],
            'chapters' => (int) $book['chapters'],
            'position' => (int) $book['ord'] - 1,
        ], $booksCatalog),
        'chapters' => $chapters,
    ];
    $json = json_encode($pack, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $compressed = gzencode($json, 9);
    if ($compressed === false) {
        throw new RuntimeException("No se pudo comprimir el pack {$code}");
    }
    $target = rtrim($targetDir, DIRECTORY_SEPARATOR) . "/bible_pack_{$code}.gz";
    if (file_put_contents($target, $compressed) === false) {
        throw new RuntimeException("No se pudo escribir {$target}");
    }
    echo $code . ': ' . number_format(strlen($json)) . ' bytes sin comprimir, '
        . number_format(strlen($compressed)) . " bytes comprimidos\n";
}
