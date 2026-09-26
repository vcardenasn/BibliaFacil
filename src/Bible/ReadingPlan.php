<?php

namespace Biblia\Bible;

/**
 * EPIC 05 — planes de lectura (US-040/US-041).
 * Reparte capítulos consecutivos en N días de forma pareja; el progreso
 * vive en el navegador (localStorage), aquí solo se calcula la estructura.
 */
final class ReadingPlan
{
    /** Capítulos del plan en orden canónico: [['slug','name','ch'], …] */
    public static function readings(array $plan, array $books): array
    {
        $sel = $plan['books'] ?? 'all';
        $list = [];
        foreach ($books as $b) {
            if ($sel === 'nt' && ($b['testament'] ?? '') !== 'NT') { continue; }
            if ($sel === 'at' && ($b['testament'] ?? '') !== 'AT') { continue; }
            if (is_array($sel) && !in_array($b['slug'], $sel, true)) { continue; }
            for ($c = 1; $c <= (int) $b['chapters']; $c++) {
                $list[] = ['slug' => $b['slug'], 'name' => $b['name'], 'ch' => $c];
            }
        }
        return $list;
    }

    /**
     * Días del plan: [['n'=>1,'label'=>'Génesis 1–4','items'=>[…]], …]
     * Reparto parejo: el día d cubre hasta round(d·total/días) capítulos.
     */
    public static function days(array $plan, array $books): array
    {
        $items = self::readings($plan, $books);
        $total = count($items);
        $days = max(1, (int) ($plan['days'] ?? 1));
        $out = [];
        $start = 0;
        for ($d = 1; $d <= $days; $d++) {
            $end = (int) round($d * $total / $days);
            $chunk = array_slice($items, $start, $end - $start);
            $start = $end;
            if (!$chunk) { break; }
            $out[] = ['n' => $d, 'label' => self::label($chunk), 'items' => $chunk];
        }
        return $out;
    }

    private static function label(array $chunk): string
    {
        $a = $chunk[0];
        $b = $chunk[count($chunk) - 1];
        if ($a['slug'] === $b['slug']) {
            return $a['ch'] === $b['ch']
                ? "{$a['name']} {$a['ch']}"
                : "{$a['name']} {$a['ch']}–{$b['ch']}";
        }
        return "{$a['name']} {$a['ch']} – {$b['name']} {$b['ch']}";
    }
}
