<?php

namespace Biblia\Games;

use Biblia\Bible\BibleRepository;

/**
 * US-172 — Genera rondas de "Completa el Versículo" desde la BD local.
 * Cada pregunta: versículo real con una palabra enmascarada + 3 distractores
 * tomados del mismo capítulo (plausibles pero incorrectos).
 */
final class VerseQuiz
{
    /**
     * @return array<int,array{q:string,ref:string,full:string,options:string[],a:int}>
     */
    public function round(BibleRepository $repo, int $versionId, int $n): array
    {
        $books = $repo->books();
        $out = [];
        $seen = [];
        $tries = 0;

        while (count($out) < $n && $tries++ < $n * 25) {
            $book = $books[array_rand($books)];
            $chapter = random_int(1, max(1, (int) $book['chapters']));
            $verses = $repo->chapter($versionId, (int) $book['id'], $chapter);
            $verses = array_values(array_filter($verses, function ($v) {
                $w = str_word_count(preg_replace('/[^\p{L} ]/u', ' ', (string) $v['text']) ?? '');
                return $w >= 7 && $w <= 30;
            }));
            if (!$verses) {
                continue;
            }
            $v = $verses[array_rand($verses)];
            $key = $book['osis'] . ':' . $chapter . ':' . $v['verse'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            // Palabras del versículo (con posición en el texto)
            if (!preg_match_all('/[\p{L}]{5,}/u', (string) $v['text'], $mm, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            $cands = array_slice($mm[0], 1); // nunca la primera palabra larga
            if (!$cands) {
                continue;
            }
            [$word, $byteOff] = $cands[array_rand($cands)];
            // PREG_OFFSET_CAPTURE devuelve offset en bytes → convertir a caracteres
            $off = mb_strlen(substr((string) $v['text'], 0, $byteOff));

            // Distractores: palabras largas del mismo capítulo
            $pool = [];
            foreach ($verses as $vv) {
                if (preg_match_all('/[\p{L}]{5,}/u', (string) $vv['text'], $m2)) {
                    foreach ($m2[0] as $w) {
                        if (mb_strtolower($w) !== mb_strtolower($word)) {
                            $pool[$w] = true;
                        }
                    }
                }
            }
            $pool = array_keys($pool);
            if (count($pool) < 3) {
                continue;
            }
            shuffle($pool);
            $options = array_slice($pool, 0, 3);
            $a = random_int(0, 3);
            array_splice($options, $a, 0, [$word]);

            $masked = mb_substr((string) $v['text'], 0, $off)
                . str_repeat('_', mb_strlen($word))
                . mb_substr((string) $v['text'], $off + mb_strlen($word));

            $out[] = [
                'q'       => $masked,
                'ref'     => $book['name'] . ' ' . $chapter . ':' . $v['verse'],
                'full'    => (string) $v['text'],
                'options' => $options,
                'a'       => $a,
            ];
        }
        return $out;
    }
}
