<?php

namespace Biblia\Bible;

use RuntimeException;

/**
 * Convierte un directorio de archivos USFM (formato eBible.org / DBL)
 * al JSON normalizado del proyecto.
 *
 * Salida por versículo: ['v' => int, 't' => 'texto con sentinels [wj]…[/wj]']
 * donde [wj] marca las palabras de Jesús (char style \wj … \wj*).
 *
 * Reglas:
 * - \f…\f*, \x…\x* (notas al pie, referencias) se descartan con su contenido.
 * - \s1, \sp, \cl, \mt*, \toc*, \h, \id… (metadatos/editorial) se descartan a fin de línea.
 * - \d (título de Salmos) se antepone al versículo siguiente.
 * - \v 16-17 (puentes de versículos) se registran bajo el primer número.
 * - El resto de marcadores (\p, \q*, \m, \nd, \it, \w, \+…) solo separan.
 */
final class UsfmParser
{
    /** Diferencias USFM → OSIS en el canon protestante. */
    private const USFM_TO_OSIS = ['EZR' => 'ESD', 'NAM' => 'NAH'];

    /** Marcadores cuyo contenido se descarta hasta la estrella de cierre. */
    private const DROP_UNTIL_STAR = ['f', 'x', 'fe', 'ef', 'ex', 'rq'];

    /** Marcadores cuyo resto de línea se descarta (metadatos y encabezados). */
    private const DROP_LINE = [
        'id', 'ide', 'usfm', 'sts', 'rem', 'h', 'toc1', 'toc2', 'toc3',
        'mt', 'mt1', 'mt2', 'mt3', 'mt4', 'imt1', 'imt2', 'ms', 'ms1', 'ms2',
        's', 's1', 's2', 's3', 's4', 'sp', 'cl', 'is1', 'is2', 'ip', 'ipi',
        'im', 'imt', 'iot', 'io1', 'io2', 'ie', 'iex', 'cd', 'periph', 'restore',
    ];

    /** @param array<string,int> $osisToOrd mapa osis => ord (de books.php) */
    public function __construct(private array $osisToOrd)
    {
    }

    /**
     * Parsea un directorio con archivos NN-<USFM><id>.usfm (eBible).
     * @return array<int,array{osis:string,ord:int,chapters:array<int,array<int,array{v:int,t:string}>>}>
     */
    public function parseDir(string $dir): array
    {
        if (!is_dir($dir)) {
            throw new RuntimeException("Directorio USFM no encontrado: {$dir}");
        }
        $files = glob(rtrim($dir, '/') . '/*.usfm') ?: [];
        sort($files);
        $books = [];
        foreach ($files as $file) {
            // Nombre tipo 49-JHNspaonbv.usfm → código USFM JHN (3 letras).
            if (!preg_match('/^\d{2,3}-([A-Z0-9]{3})/i', basename($file), $m)) {
                continue;
            }
            $osis = strtoupper($m[1]);
            $osis = self::USFM_TO_OSIS[$osis] ?? $osis;
            if (!isset($this->osisToOrd[$osis])) {
                continue; // deuterocanónicos, front matter, ps151, etc.
            }
            $books[] = [
                'osis' => $osis,
                'ord' => $this->osisToOrd[$osis],
                'chapters' => $this->parseFile((string) file_get_contents($file)),
            ];
        }
        usort($books, fn ($a, $b) => $a['ord'] <=> $b['ord']);
        return $books;
    }

    /**
     * Parsea el contenido USFM de UN libro.
     * @return array<int,array<int,array{v:int,t:string}>> índice cap => versículos
     */
    public function parseFile(string $content): array
    {
        $content = (string) preg_replace('/\r\n?/', "\n", $content);
        $tokens = preg_split('/\\\\([a-zA-Z+*]+\d*)\s*/u', $content, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        $chapters = [];
        $chapter = 0;
        $verse = 0;
        $buf = '';
        $wjOpen = false;    // hay un [wj] abierto en el buffer
        $wjPending = false; // \wj visto, falta el primer chunk de texto
        $dropUntil = null;  // 'f' | 'x' | … mientras dure \f…\f*
        $dropLine = false;  // hasta el siguiente \n
        $pendingTitle = ''; // contenido de \d para anteponer al próximo versículo

        $flush = function () use (&$chapters, &$buf, &$verse, &$chapter, &$wjOpen, &$wjPending) {
            if ($verse > 0) {
                $t = trim((string) preg_replace('/\s+/u', ' ', $buf));
                if ($t !== '') {
                    $chapters[$chapter][$verse] = ['v' => $verse, 't' => $t];
                }
            }
            $buf = '';
            $wjOpen = $wjPending = false;
        };

        $append = function (string $text) use (&$buf, &$wjOpen, &$wjPending, &$verse) {
            if ($verse <= 0 || $text === '') {
                return;
            }
            if ($wjPending) {
                $buf .= ' [wj]';
                $wjPending = false;
                $wjOpen = true;
            }
            $buf .= ' ' . $text;
        };

        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            if ($i % 2 === 0) {
                // ---- texto entre marcadores ----
                $text = $tokens[$i];
                if ($dropLine) {
                    $text = (string) preg_replace('/^[^\n]*\n?/u', '', $text, 1);
                    $dropLine = false;
                }
                if ($dropUntil !== null || $verse <= 0) {
                    continue;
                }
                $append(str_replace("\n", ' ', $text));
                continue;
            }

            // ---- marcador ----
            $mk = $tokens[$i];
            $star = str_ends_with($mk, '*');
            $base = strtolower(ltrim(rtrim($mk, '*'), '+'));

            if ($dropUntil !== null) {
                if ($mk === $dropUntil . '*') {
                    $dropUntil = null;
                } elseif ($base === 'v' || $base === 'c') {
                    $dropUntil = null;
                    // no continue: \v / \c se procesan abajo
                } else {
                    continue;
                }
            }

            if (in_array($base, self::DROP_UNTIL_STAR, true) && !$star) {
                $dropUntil = $base;
                continue;
            }
            if ($base === 'wj') {
                if ($star) {
                    if ($wjOpen) {
                        $buf .= '[/wj]';
                        $wjOpen = false;
                    }
                    $wjPending = false;
                } elseif ($verse > 0 && !$wjOpen) {
                    $wjPending = true;
                }
                continue;
            }
            if ($base === 'v') {
                $flush();
                // El número viene al inicio del siguiente token de texto.
                $num = $tokens[$i + 1] ?? '';
                if (preg_match('/^\s*(\d+)/u', $num, $nm)) {
                    $verse = (int) $nm[1];
                    $tokens[$i + 1] = substr($num, strlen($nm[0]));
                } else {
                    $verse = 0;
                }
                if ($pendingTitle !== '') {
                    $buf = ' ' . $pendingTitle;
                    $pendingTitle = '';
                }
                continue;
            }
            if ($base === 'c') {
                $flush();
                $verse = 0;
                $num = $tokens[$i + 1] ?? '';
                if (preg_match('/^\s*(\d+)/u', $num, $nm)) {
                    $chapter = (int) $nm[1];
                    $tokens[$i + 1] = substr($num, strlen($nm[0]));
                }
                continue;
            }
            if ($base === 'd') {
                // Título de Salmo: se antepone al próximo versículo.
                $text = $tokens[$i + 1] ?? '';
                $lineEnd = strpos($text, "\n");
                $pendingTitle = trim((string) preg_replace('/\s+/u', ' ', $lineEnd === false ? $text : substr($text, 0, $lineEnd)));
                continue;
            }
            if (in_array($base, self::DROP_LINE, true)) {
                $dropLine = true;
                continue;
            }
            // resto: \p \q* \m \b \nd \it \w \+… → no-op, el texto fluye.
        }
        $flush();

        // Libros de un capítulo sin \c explícito → capítulo 1.
        if (isset($chapters[0])) {
            $chapters[1] = array_merge($chapters[1] ?? [], $chapters[0]);
            unset($chapters[0]);
        }
        ksort($chapters);
        foreach ($chapters as &$vs) {
            ksort($vs);
        }
        return array_map('array_values', $chapters);
    }
}
