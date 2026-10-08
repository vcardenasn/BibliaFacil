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
}
