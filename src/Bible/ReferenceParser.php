<?php

namespace Biblia\Bible;

/**
 * Convierte texto libre ("juan 3:16", "1 corintios 13", "salmo 23")
 * en una referencia estructurada. El usuario escribe lo que sabe;
 * la app nunca lo obliga a navegar árboles.
 */
final class ReferenceParser
{
    /** @var array<string,string> alias normalizado => osis */
    private array $aliasMap = [];

    /**
     * @param array<int,array> $books filas del catálogo (osis, aliases CSV, slug)
     */
    public function __construct(array $books)
    {
        foreach ($books as $book) {
            $aliases = array_merge(
                [$book['name'], $book['slug'], $book['osis']],
                explode(',', (string) $book['aliases'])
            );
            foreach ($aliases as $alias) {
                $alias = self::normalize($alias);
                if ($alias !== '') {
                    $this->aliasMap[$alias] = $book['osis'];
                }
            }
        }
        // Los alias más largos primero: "1 juan" gana contra "juan".
        uksort($this->aliasMap, fn ($a, $b) => strlen($b) <=> strlen($a));
    }

    /** @return array{osis:string,chapter:int,verse:?int,verse_end:?int}|null */
    public function parse(string $input): ?array
    {
        $input = self::normalize($input);
        // "1cor" → "1 cor", "2pedro" → "2 pedro": normaliza número pegado a letra.
        $input = (string) preg_replace('/^([123])\s*([a-z])/u', '$1 $2', $input);
        if ($input === '') {
            return null;
        }
        foreach ($this->aliasMap as $alias => $osis) {
            if (!str_starts_with($input, $alias)) {
                continue;
            }
            $rest = trim(substr($input, strlen($alias)));
            // El alias debe terminar en frontera de palabra (evita "juana"→juan).
            if ($rest !== '' && !preg_match('/^[\s.\-:]/u', $rest) && !ctype_digit($rest[0] ?? '')) {
                // "1juan" sí se acepta vía alias compuesto; "juanita" no.
                if (!str_starts_with($rest, ' ')) {
                    continue;
                }
            }
            $rest = trim($rest, " .\t\n\r-:");
            if ($rest === '') {
                return ['osis' => $osis, 'chapter' => 1, 'verse' => null, 'verse_end' => null];
            }
            if (!preg_match('/^(\d{1,3})\s*(?:[:.,]\s*(\d{1,3})\s*(?:[-–]\s*(\d{1,3}))?)?$/u', $rest, $m)) {
                return null;
            }
            return [
                'osis' => $osis,
                'chapter' => (int) $m[1],
                'verse' => isset($m[2]) && $m[2] !== '' ? (int) $m[2] : null,
                'verse_end' => isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null,
            ];
        }
        return null;
    }

    public static function normalize(string $value): string
    {
        $value = unaccent($value);
        $value = (string) preg_replace('/\s+/u', ' ', $value);
        return trim($value);
    }
}
