<?php

namespace Biblia\Bible;

use Biblia\Core\Database;
use PDO;
use Throwable;

final class BibleRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getPdo();
    }

    /** @return array<int,array> versiones visibles (open/approved + active) */
    public function versions(): array
    {
        return $this->pdo->query(
            "SELECT * FROM versions
             WHERE active = 1 AND license_status IN ('open','approved')
             ORDER BY language = 'es' DESC, code"
        )->fetchAll();
    }

    public function versionByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM versions
             WHERE code = :code AND active = 1 AND license_status IN ('open','approved')"
        );
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int,array> 66 libros ordenados */
    public function books(): array
    {
        return $this->pdo->query('SELECT * FROM books ORDER BY ord')->fetchAll();
    }

    /** Libro por slug, osis u ord. */
    public function book(string $identifier): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM books WHERE slug = :s OR osis = :o OR ord = :n LIMIT 1'
        );
        $stmt->execute([
            's' => $identifier,
            'o' => strtoupper($identifier),
            'n' => ctype_digit($identifier) ? (int) $identifier : -1,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static ?bool $hasWj = null;
    private static ?bool $hasApiBible = null;
    /** @var array<int,?string> version_id → api_bible_id */
    private array $apiIds = [];
    private ApiBibleService|false|null $api = false;

    /** La columna verses.wj es opcional (upgrade_verses_wj.sql en prod). */
    private function hasWj(): bool
    {
        if (self::$hasWj === null) {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            self::$hasWj = $driver === 'sqlite'
                ? (bool) $this->pdo->query("SELECT 1 FROM pragma_table_info('verses') WHERE name = 'wj'")->fetch()
                : (bool) $this->pdo->query("SHOW COLUMNS FROM verses LIKE 'wj'")->fetch();
        }
        return self::$hasWj;
    }

    /** La columna versions.api_bible_id es opcional (upgrade_versions_api_bible.sql). */
    private function hasApiBible(): bool
    {
        if (self::$hasApiBible === null) {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            self::$hasApiBible = $driver === 'sqlite'
                ? (bool) $this->pdo->query("SELECT 1 FROM pragma_table_info('versions') WHERE name = 'api_bible_id'")->fetch()
                : (bool) $this->pdo->query("SHOW COLUMNS FROM versions LIKE 'api_bible_id'")->fetch();
        }
        return self::$hasApiBible;
    }

    private function apiService(): ?ApiBibleService
    {
        if ($this->api === false) {
            $this->api = ApiBibleService::make();
        }
        return $this->api;
    }

    /** api_bible_id de la versión, o null si es versión local. */
    private function apiBibleId(int $versionId): ?string
    {
        if (!$this->hasApiBible()) {
            return null;
        }
        if (!array_key_exists($versionId, $this->apiIds)) {
            $stmt = $this->pdo->prepare('SELECT api_bible_id FROM versions WHERE id = :i');
            $stmt->execute(['i' => $versionId]);
            $id = $stmt->fetchColumn();
            $this->apiIds[$versionId] = $id === false || $id === null || $id === '' ? null : (string) $id;
        }
        return $this->apiIds[$versionId];
    }

    private function bookById(int $bookId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM books WHERE id = :i');
        $stmt->execute(['i' => $bookId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int,array> versículos de un capítulo */
    public function chapter(int $versionId, int $bookId, int $chapter): array
    {
        // Versión licenciada vía API.Bible: se sirve por API (sin copia en BD).
        $bibleId = $this->apiBibleId($versionId);
        if ($bibleId !== null && ($api = $this->apiService()) !== null) {
            $book = $this->bookById($bookId);
            if ($book) {
                try {
                    return $api->chapterVerses($bibleId, (string) $book['osis'], $chapter);
                } catch (Throwable $e) {
                    error_log('API.Bible chapter: ' . $e->getMessage());
                    return [];
                }
            }
        }

        $stmt = $this->pdo->prepare(
            'SELECT verse, text' . ($this->hasWj() ? ', wj' : '') . ' FROM verses
             WHERE version_id = :v AND book_id = :b AND chapter = :c
             ORDER BY verse'
        );
        $stmt->execute(['v' => $versionId, 'b' => $bookId, 'c' => $chapter]);
        return $stmt->fetchAll();
    }

    /**
     * Navegación anterior/siguiente cruzando límites de libro.
     * @return array{prev:?string,next:?string} rutas relativas
     */
    public function chapterNav(array $version, array $book, int $chapter): array
    {
        $books = $this->books();
        $idx = array_search($book['ord'], array_column($books, 'ord'), true);
        $prev = null;
        $next = null;
        if ($chapter > 1) {
            $prev = "/{$version['code']}/{$book['slug']}/" . ($chapter - 1);
        } elseif ($idx > 0) {
            $pb = $books[$idx - 1];
            $prev = "/{$version['code']}/{$pb['slug']}/{$pb['chapters']}";
        }
        if ($chapter < (int) $book['chapters']) {
            $next = "/{$version['code']}/{$book['slug']}/" . ($chapter + 1);
        } elseif ($idx !== false && $idx < count($books) - 1) {
            $nb = $books[$idx + 1];
            $next = "/{$version['code']}/{$nb['slug']}/1";
        }
        return ['prev' => $prev, 'next' => $next];
    }

    /** Búsqueda: MATCH AGAINST en MySQL, LIKE en SQLite; endpoint /search para API.Bible. */
    public function search(int $versionId, string $query, int $limit = 50): array
    {
        $query = trim($query);
        $bibleId = $this->apiBibleId($versionId);
        if ($bibleId !== null && ($api = $this->apiService()) !== null && mb_strlen($query) >= 3) {
            try {
                $booksByOsis = array_column(
                    $this->pdo->query('SELECT * FROM books')->fetchAll(),
                    null,
                    'osis'
                );
                $out = [];
                foreach ($api->search($bibleId, $query, $limit) as $r) {
                    $b = $booksByOsis[$r['osis']] ?? null;
                    if (!$b) {
                        continue;
                    }
                    $out[] = [
                        'book_id' => $b['id'],
                        'chapter' => $r['chapter'],
                        'verse' => $r['verse'],
                        'text' => $r['text'],
                        'book_name' => $b['name'],
                        'book_slug' => $b['slug'],
                    ];
                }
                return $out;
            } catch (Throwable $e) {
                error_log('API.Bible search: ' . $e->getMessage());
                return [];
            }
        }
        if ($query === '' || mb_strlen($query) < 3) {
            return [];
        }
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $sql = "SELECT v.book_id, v.chapter, v.verse, v.text, b.name AS book_name, b.slug AS book_slug
                    FROM verses v JOIN books b ON b.id = v.book_id
                    WHERE v.version_id = :v AND MATCH(v.text) AGAINST (:q IN NATURAL LANGUAGE MODE)
                    ORDER BY b.ord, v.chapter, v.verse LIMIT :lim";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('v', $versionId, PDO::PARAM_INT);
            $stmt->bindValue('q', $query);
            $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        } else {
            $sql = "SELECT v.book_id, v.chapter, v.verse, v.text, b.name AS book_name, b.slug AS book_slug
                    FROM verses v JOIN books b ON b.id = v.book_id
                    WHERE v.version_id = :v AND v.text LIKE :q
                    ORDER BY b.ord, v.chapter, v.verse LIMIT :lim";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('v', $versionId, PDO::PARAM_INT);
            $stmt->bindValue('q', '%' . $query . '%');
            $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Referencias rotativas del versículo del día. */
    public const VOTD_REFS = [
        ['JHN', 3, 16], ['PSA', 23, 1], ['PRO', 3, 5], ['ROM', 8, 28],
        ['PHP', 4, 13], ['JER', 29, 11], ['ISA', 41, 10], ['MAT', 11, 28],
        ['PSA', 46, 1], ['ROM', 12, 2], ['GAL', 5, 22], ['EPH', 2, 8],
        ['JOS', 1, 9], ['PSA', 119, 105], ['PRO', 22, 6], ['MAT', 6, 33],
        ['1CO', 13, 4], ['PSA', 37, 4], ['ISA', 40, 31], ['HEB', 11, 1],
        ['JHN', 14, 6], ['ROM', 5, 8], ['PSA', 91, 1], ['1PE', 5, 7],
        ['COL', 3, 23], ['PRO', 16, 3], ['LAM', 3, 22], ['MIC', 6, 8],
        ['REV', 21, 4], ['DEU', 31, 6],
    ];

    /** Versículo puntual por referencia (osis, capítulo, versículo). */
    public function verseByRef(int $versionId, string $osis, int $chapter, int $verse): ?array
    {
        $bibleId = $this->apiBibleId($versionId);
        if ($bibleId !== null && ($api = $this->apiService()) !== null) {
            try {
                $v = $api->verse($bibleId, $osis, $chapter, $verse);
            } catch (Throwable $e) {
                error_log('API.Bible verse: ' . $e->getMessage());
                $v = null;
            }
            if (!$v) {
                return null;
            }
            $b = $this->book($osis);
            if (!$b) {
                return null;
            }
            return [
                'text' => $v['text'],
                'wj' => $v['wj'] ?? null,
                'book_name' => $b['name'],
                'book_slug' => $b['slug'],
                'chapter' => $chapter,
                'verse' => $verse,
            ];
        }
        $stmt = $this->pdo->prepare(
            'SELECT v.text, v.wj, b.name AS book_name, b.slug AS book_slug, v.chapter, v.verse
             FROM verses v JOIN books b ON b.id = v.book_id
             WHERE v.version_id = :v AND b.osis = :o AND v.chapter = :c AND v.verse = :n'
        );
        if (!$this->hasWj()) {
            $stmt = $this->pdo->prepare(
                'SELECT v.text, NULL AS wj, b.name AS book_name, b.slug AS book_slug, v.chapter, v.verse
                 FROM verses v JOIN books b ON b.id = v.book_id
                 WHERE v.version_id = :v AND b.osis = :o AND v.chapter = :c AND v.verse = :n'
            );
        }
        $stmt->execute(['v' => $versionId, 'o' => strtoupper($osis), 'c' => $chapter, 'n' => $verse]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Lista de versículos por refs [[osis, cap, ver], …] → mismas filas que verseByRef. */
    public function versesByRefs(int $versionId, array $refs): array
    {
        $out = [];
        foreach ($refs as [$o, $c, $n]) {
            $row = $this->verseByRef($versionId, $o, (int) $c, (int) $n);
            if ($row) {
                $out[] = $row;
            }
        }
        return $out;
    }

    /** Versículo del día: referencia rotativa determinística (acecha ?d=YYYY-MM-DD). */
    public function verseOfTheDay(int $versionId, ?string $date = null): ?array
    {
        $refs = self::VOTD_REFS;
        $z = $date ? (int) date('z', strtotime($date)) : (int) date('z');
        $ref = $refs[$z % count($refs)];
        return $this->verseByRef($versionId, $ref[0], $ref[1], $ref[2]);
    }

    /** Conteo de versículos por versión (import status / check). */
    public function verseCounts(): array
    {
        return $this->pdo->query(
            'SELECT v.code, COUNT(s.id) AS total
             FROM versions v LEFT JOIN verses s ON s.version_id = v.id
             GROUP BY v.id ORDER BY v.code'
        )->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
