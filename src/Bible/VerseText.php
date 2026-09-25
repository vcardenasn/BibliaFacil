<?php

namespace Biblia\Bible;

/**
 * Texto de versículo con markup ligero de palabras de Jesús.
 *
 * Las fuentes JSON guardan el texto con sentinels [wj]…[/wj]; de ahí:
 * - split()  → [texto plano, JSON de rangos [[ini,len],…] o null] para la BD
 * - render() → HTML escapado con <em class="wj">…</em> para la vista
 */
final class VerseText
{
    /** @return array{0:string,1:?string} [texto limpio, json wj|null] */
    public static function split(string $raw): array
    {
        if (!str_contains($raw, '[wj]')) {
            return [trim((string) preg_replace('/\s+/u', ' ', strip_tags($raw))), null];
        }

        $clean = '';
        $ranges = [];
        $open = null;
        foreach (preg_split('/(\[\/?wj\])/u', $raw, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $seg) {
            if ($seg === '[wj]') {
                if ($open === null) {
                    $open = mb_strlen($clean);
                }
                continue;
            }
            if ($seg === '[/wj]') {
                if ($open !== null) {
                    $len = mb_strlen($clean) - $open;
                    if ($len > 0) {
                        $ranges[] = [$open, $len];
                    }
                    $open = null;
                }
                continue;
            }
            $seg = (string) preg_replace('/\s+/u', ' ', strip_tags($seg));
            // Une sin dobles espacios: los offsets se miden sobre el texto final.
            if ($clean !== '' && str_ends_with($clean, ' ') && str_starts_with($seg, ' ')) {
                $seg = substr($seg, 1);
            }
            $clean .= $seg;
        }
        if ($open !== null) {
            $ranges[] = [$open, mb_strlen($clean) - $open];
        }

        // Si el espacio inicial cayó DENTRO de un rango wj, el rango se
        // clampea a 0 (no se descarta); lo que sobra se recorta del largo.
        $lead = mb_strlen($clean) - mb_strlen(ltrim($clean));
        if ($lead > 0) {
            $clean = ltrim($clean);
            $ranges = array_map(
                fn ($r) => [max(0, $r[0] - $lead), $r[1] - max(0, $lead - $r[0])],
                $ranges
            );
        }
        $clean = rtrim($clean);

        // Recorta espacios dentro de los bordes de cada rango.
        $total = mb_strlen($clean);
        $ranges = array_values(array_filter(array_map(function ($r) use ($clean, $total) {
            [$s, $l] = $r;
            while ($l > 0 && $s < $total && mb_substr($clean, $s, 1) === ' ') {
                $s++;
                $l--;
            }
            while ($l > 0 && mb_substr($clean, $s + $l - 1, 1) === ' ') {
                $l--;
            }
            return [$s, $l];
        }, $ranges), fn ($r) => $r[1] > 0 && $r[0] < $total));

        return [$clean, $ranges ? json_encode($ranges) : null];
    }

    /**
     * HTML seguro del versículo: escapa todo y envuelve rangos wj en <em class="wj">.
     */
    public static function render(string $text, ?string $wjJson): string
    {
        $ranges = $wjJson ? json_decode($wjJson, true) : null;
        if (!is_array($ranges) || $ranges === []) {
            return e($text);
        }
        $html = '';
        $pos = 0;
        foreach ($ranges as $r) {
            [$s, $l] = [(int) ($r[0] ?? 0), (int) ($r[1] ?? 0)];
            if ($l <= 0 || $s < $pos || $s > mb_strlen($text)) {
                continue;
            }
            $html .= e(mb_substr($text, $pos, $s - $pos));
            $html .= '<em class="wj">' . e(mb_substr($text, $s, $l)) . '</em>';
            $pos = $s + $l;
        }
        return $html . e(mb_substr($text, $pos));
    }
}
