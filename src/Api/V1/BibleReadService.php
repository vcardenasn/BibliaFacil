<?php

namespace Biblia\Api\V1;

use Biblia\Bible\BibleRepository;

final class BibleReadService
{
    public function __construct(private BibleRepository $repository)
    {
    }

    public function catalog(): array
    {
        $versions = array_values(array_filter(
            $this->repository->versions(),
            static fn (array $version): bool => empty($version['api_bible_id'])
        ));

        return [
            'versions' => array_map(static fn (array $version): array => [
                'code' => (string) $version['code'],
                'name' => (string) $version['name'],
                'language' => (string) $version['language'],
                'license' => (string) $version['license'],
                'license_status' => (string) $version['license_status'],
                'offline_download_allowed' => OfflineLicensePolicy::allows((string) $version['license']),
                'attribution' => $version['copyright'] === null ? null : (string) $version['copyright'],
                'content_source' => 'local',
            ], $versions),
            'books' => array_map(static fn (array $book): array => [
                'slug' => (string) $book['slug'],
                'osis' => (string) $book['osis'],
                'name' => (string) $book['name'],
                'order' => (int) $book['ord'],
                'testament' => (string) $book['testament'],
                'chapters' => (int) $book['chapters'],
            ], $this->repository->books()),
        ];
    }

    public function chapter(string $versionCode, string $bookSlug, int $chapter): ?array
    {
        $version = $this->repository->versionByCode($versionCode);
        $book = $this->repository->book($bookSlug);
        if (!$version || !empty($version['api_bible_id']) || !$book
            || $chapter < 1 || $chapter > (int) $book['chapters']) {
            return null;
        }

        $verses = array_map(static function (array $verse): array {
            $wj = isset($verse['wj']) ? json_decode((string) $verse['wj'], true) : null;
            return [
                'number' => (int) $verse['verse'],
                'text' => (string) $verse['text'],
                'wj' => is_array($wj) ? $wj : null,
            ];
        }, $this->repository->chapter((int) $version['id'], (int) $book['id'], $chapter));

        return [
            'version' => [
                'code' => (string) $version['code'],
                'name' => (string) $version['name'],
                'language' => (string) $version['language'],
            ],
            'book' => [
                'slug' => (string) $book['slug'],
                'osis' => (string) $book['osis'],
                'name' => (string) $book['name'],
                'testament' => (string) $book['testament'],
            ],
            'chapter' => $chapter,
            'content_source' => 'local',
            'attribution' => $version['copyright'] === null ? null : (string) $version['copyright'],
            'verses' => $verses,
        ];
    }

    public function topics(): array
    {
        $out = [];
        foreach ((array) config('temas') as $slug => $topic) {
            $out[] = [
                'slug' => (string) $slug,
                'name' => t((string) ($topic['name'] ?? $slug)),
                'emoji' => (string) ($topic['emoji'] ?? ''),
                'desc' => t((string) ($topic['desc'] ?? '')),
                'references' => count((array) ($topic['refs'] ?? [])),
            ];
        }
        return $out;
    }

    public function topic(string $slug, string $versionCode): ?array
    {
        $topic = ((array) config('temas'))[$slug] ?? null;
        $version = $this->repository->versionByCode($versionCode);
        if ($topic === null || !$version || !empty($version['api_bible_id'])) {
            return null;
        }
        $refs = array_map(static fn (array $ref): array => [
            'osis' => (string) $ref[0],
            'chapter' => (int) $ref[1],
            'verse' => (int) $ref[2],
        ], (array) ($topic['refs'] ?? []));
        $verses = array_map(static function (array $row): array {
            $wj = is_array($row['wj'] ?? null)
                ? $row['wj']
                : (is_string($row['wj'] ?? null) ? json_decode($row['wj'], true) : null);
            return [
                'book' => ['slug' => (string) $row['book_slug'], 'name' => (string) $row['book_name']],
                'chapter' => (int) $row['chapter'],
                'verse' => (int) $row['verse'],
                'text' => (string) $row['text'],
                'wj' => is_array($wj) ? $wj : null,
            ];
        }, $this->repository->versesByRefs((int) $version['id'], (array) ($topic['refs'] ?? [])));
        return [
            'slug' => (string) $slug,
            'name' => t((string) ($topic['name'] ?? $slug)),
            'emoji' => (string) ($topic['emoji'] ?? ''),
            'desc' => t((string) ($topic['desc'] ?? '')),
            'intro' => t((string) ($topic['intro'] ?? '')),
            'version' => [
                'code' => (string) $version['code'],
                'name' => (string) $version['name'],
            ],
            'refs' => $refs,
            'verses' => $verses,
        ];
    }

    public function verseOfTheDay(string $versionCode): ?array
    {
        $version = $this->repository->versionByCode($versionCode);
        if (!$version || !empty($version['api_bible_id'])) {
            return null;
        }
        $row = $this->repository->verseOfTheDay((int) $version['id']);
        if ($row === null) {
            return null;
        }
        return [
            'ref' => "{$row['book_name']} {$row['chapter']}:{$row['verse']}",
            'book' => ['slug' => (string) $row['book_slug'], 'name' => (string) $row['book_name']],
            'chapter' => (int) $row['chapter'],
            'verse' => (int) $row['verse'],
            'text' => (string) $row['text'],
            'version' => [
                'code' => (string) $version['code'],
                'name' => (string) $version['name'],
            ],
            'date' => date('Y-m-d'),
        ];
    }

    public function plans(): array
    {
        $out = [];
        foreach ((array) config('plans') as $slug => $plan) {
            $books = $plan['books'] ?? 'all';
            $out[] = [
                'slug' => (string) $slug,
                'name' => t((string) ($plan['name'] ?? $slug)),
                'emoji' => (string) ($plan['emoji'] ?? ''),
                'desc' => t((string) ($plan['desc'] ?? '')),
                'intro' => t((string) ($plan['intro'] ?? '')),
                'days' => (int) ($plan['days'] ?? 1),
                'books' => is_array($books)
                    ? ['filter' => 'list', 'slugs' => array_values($books)]
                    : ['filter' => (string) $books, 'slugs' => null],
            ];
        }
        return $out;
    }
}
