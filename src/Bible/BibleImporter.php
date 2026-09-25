<?php

namespace Biblia\Bible;

use PDO;
use RuntimeException;

/**
 * Importa una versión completa a `verses` desde JSON normalizado o
 * formato scrollmapper ({translation, books:[{name,chapters:[{chapter,verses:[{verse,text}]}]}]}).
 * Idempotente: SELECT → INSERT (portable SQLite/MySQL).
 */
final class BibleImporter
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array{version:string,inserted:int,skipped:int,books:int} */
    public function importFile(string $file, string $code): array
    {
        if (!is_file($file)) {
            throw new RuntimeException("Archivo no encontrado: {$file}");
        }
        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data) || empty($data['books'])) {
            throw new RuntimeException("Formato no reconocido en {$file}");
        }

        $version = $this->versionId($code);
        $bookIds = $this->bookIdsByOrd();
        $books = array_values($data['books']);

        // Solo canon protestante: 66 libros en orden; se ignoran extras (deuterocanónicos).
        if (count($books) < 66) {
            throw new RuntimeException('Fuente incompleta: ' . count($books) . ' libros (<66)');
        }

        $check = $this->pdo->prepare(
            'SELECT id FROM verses WHERE version_id = :v AND book_id = :b AND chapter = :c AND verse = :n'
        );
        $insert = $this->pdo->prepare(
            'INSERT INTO verses (version_id, book_id, chapter, verse, text, wj)
             VALUES (:v, :b, :c, :n, :t, :w)'
        );

        $inserted = 0;
        $skipped = 0;
        $this->pdo->beginTransaction();
        try {
            foreach (array_slice($books, 0, 66) as $i => $book) {
                $bookId = $bookIds[$i + 1] ?? null;
                if ($bookId === null) {
                    throw new RuntimeException("Falta libro ord=" . ($i + 1) . ' en tabla books — correr seed.php');
                }
                foreach ($book['chapters'] as $ci => $ch) {
                    // Dos formatos: [{chapter,verses:[{verse,text}]}] o [[v1,v2,...]]
                    $verses = isset($ch['verses']) ? $ch['verses'] : $ch;
                    $chapter = isset($ch['chapter']) ? (int) $ch['chapter'] : $ci + 1;
                    foreach ($verses as $vi => $v) {
                        // Formatos: "texto", {verse,text} o USFM normalizado {v,t} con sentinels [wj].
                        $raw = is_array($v) ? (string) ($v['t'] ?? $v['text'] ?? '') : (string) $v;
                        [$text, $wj] = VerseText::split($raw);
                        if ($text === '') {
                            continue;
                        }
                        $params = [
                            'v' => $version,
                            'b' => $bookId,
                            'c' => $chapter,
                            'n' => is_array($v) ? (int) ($v['v'] ?? $v['verse'] ?? $vi + 1) : $vi + 1,
                        ];
                        $check->execute($params);
                        if ($check->fetch()) {
                            $skipped++;
                            continue;
                        }
                        $params['t'] = $text;
                        $params['w'] = $wj;
                        $insert->execute($params);
                        $inserted++;
                    }
                }
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        return ['version' => $code, 'inserted' => $inserted, 'skipped' => $skipped, 'books' => min(66, count($books))];
    }

    private function versionId(string $code): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM versions WHERE code = :c');
        $stmt->execute(['c' => $code]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            throw new RuntimeException("Versión '{$code}' no existe — correr seed.php");
        }
        return (int) $id;
    }

    /** @return array<int,int> ord => book_id */
    private function bookIdsByOrd(): array
    {
        return $this->pdo->query('SELECT ord, id FROM books')->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** Limpia markup residual (tags, Strong's) y normaliza espacios. */
    private function clean(string $text): string
    {
        return VerseText::split($text)[0];
    }
}
