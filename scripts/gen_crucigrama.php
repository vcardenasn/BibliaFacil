<?php

/**
 * Generador de mini-crucigramas → public/assets/games/crucigrama.json
 * Uso: php scripts/gen_crucigrama.php
 *
 * Coloca palabras en una cuadrícula respetando las reglas del crucigrama:
 * se cruzan por letras iguales y ninguna letra toca otra palabra de costado.
 * Intenta N semillas por puzzle y se queda con la primera que coloca todo.
 */

$PUZZLES = [
    [
        'name' => 'Personajes bíblicos', 'icon' => '🧔', 'size' => 9,
        'words' => [
            ['MOISES', 'Guio al pueblo y partió el mar'],
            ['DAVID', 'Venció al gigante con una honda'],
            ['NOE', 'Construyó un gran arca'],
            ['EVA', 'La primera mujer'],
            ['JONAS', 'Estuvo dentro del gran pez'],
            ['ESTER', 'La reina que salvó a su pueblo'],
            ['DANIEL', 'Oró en el foso de los leones'],
            ['RUT', 'No abandonó a su suegra Noemí'],
        ],
    ],
    [
        'name' => 'Amigos de Jesús', 'icon' => '✝️', 'size' => 9,
        'words' => [
            ['PEDRO', 'Pescador que caminó sobre el agua'],
            ['MARIA', 'La madre de Jesús'],
            ['JUAN', 'El discípulo amado'],
            ['TOMAS', 'Dudó hasta ver a Jesús resucitado'],
            ['LAZARO', 'Amigo a quien Jesús resucitó'],
            ['PABLO', 'Escribió muchas cartas del NT'],
            ['MARTA', 'Hermana de María, muy servicial'],
            ['MATEO', 'Recaudador que siguió a Jesús'],
        ],
    ],
    [
        'name' => 'Palabras de la fe', 'icon' => '📖', 'size' => 9,
        'words' => [
            ['LUZ', '"Haya ___", dijo Dios el primer día'],
            ['MAR', 'Lo que Moisés partió en dos'],
            ['PAN', 'Alimento que Jesús multiplicó'],
            ['FE', 'Confianza en lo que no se ve (Hebreos 11:1)'],
            ['CRUZ', 'Donde Jesús murió por nosotros'],
            ['EDEN', 'El primer jardín'],
            ['SOL', 'El astro que alumbra el día'],
            ['OVEJA', 'El animalito que el pastor fue a buscar'],
            ['REY', 'David llegó a ser ___ de Israel'],
            ['CIELO', 'Arriba, donde Dios puso las estrellas'],
            ['ARCA', 'El barco que salvó a Noé y los animales'],
        ],
    ],
];

/** ¿La palabra cabe en (r,c) con dir (dr,dc)? Reglas: letra igual o celda vacía,
 *  sin vecinos perpendiculares salvo en cruces, bordes limpios. */
function fits(array $grid, int $S, string $w, int $r, int $c, int $dr, int $dc): bool
{
    $L = strlen($w);
    $er = $r + $dr * ($L - 1);
    $ec = $c + $dc * ($L - 1);
    if ($er < 0 || $er >= $S || $ec < 0 || $ec >= $S) {
        return false;
    }
    // antes y después de la palabra: vacío o fuera
    if ($r - $dr >= 0 && $c - $dc >= 0 && $r - $dr < $S && $c - $dc < $S && $grid[$r - $dr][$c - $dc]) {
        return false;
    }
    if ($er + $dr < $S && $ec + $dc < $S && $grid[$er + $dr][$ec + $dc]) {
        return false;
    }
    $cross = 0;
    for ($k = 0; $k < $L; $k++) {
        $rr = $r + $dr * $k;
        $cc = $c + $dc * $k;
        $cur = $grid[$rr][$cc];
        if ($cur !== null && $cur !== $w[$k]) {
            return false;
        }
        if ($cur === $w[$k]) {
            $cross++;
            continue;
        }
        // celda vacía: los costados perpendiculares deben estar vacíos
        foreach ([[$dc, $dr], [-$dc, -$dr]] as [$pr, $pc]) {
            $nr = $rr + $pr;
            $nc = $cc + $pc;
            if ($nr >= 0 && $nr < $S && $nc >= 0 && $nc < $S && $grid[$nr][$nc] !== null) {
                return false;
            }
        }
    }
    return true;
}

function buildPuzzle(array $words, int $S, int $tries = 3000): ?array
{
    $best = null;
    for ($seed = 1; $seed <= $tries; $seed++) {
        mt_srand($seed);
        $pending = $words;
        shuffle($pending);
        usort($pending, fn ($a, $b) => strlen($b[0]) <=> strlen($a[0]));

        $grid = array_fill(0, $S, array_fill(0, $S, null));
        $placed = [];

        // Pasadas: lo que no cabe en una ronda se reintenta cuando ya hay más letras
        for ($pass = 0; $pass < 3 && $pending; $pass++) {
            $rest = [];
            foreach ($pending as [$w, $clue]) {
                $cands = [];
                for ($r = 0; $r < $S; $r++) {
                    for ($c = 0; $c < $S; $c++) {
                        foreach ([[0, 1, 'h'], [1, 0, 'v']] as [$dr, $dc, $dir]) {
                            if (!fits($grid, $S, $w, $r, $c, $dr, $dc)) {
                                continue;
                            }
                            // cuenta cruces: tras la primera palabra exige ≥1
                            $shares = 0;
                            for ($k = 0; $k < strlen($w); $k++) {
                                if ($grid[$r + $dr * $k][$c + $dc * $k] === $w[$k]) {
                                    $shares++;
                                }
                            }
                            if ($placed && $shares === 0) {
                                continue;
                            }
                            $cands[] = [$r, $c, $dr, $dc, $dir, $shares];
                        }
                    }
                }
                if (!$cands) {
                    $rest[] = [$w, $clue];
                    continue;
                }
                // prefiere cruces múltiples (más compacto)
                usort($cands, fn ($a, $b) => $b[5] <=> $a[5]);
                $top = array_slice($cands, 0, max(1, intdiv(count($cands), 3)));
                [$r, $c, $dr, $dc, $dir] = $top[array_rand($top)];
                for ($k = 0; $k < strlen($w); $k++) {
                    $grid[$r + $dr * $k][$c + $dc * $k] = $w[$k];
                }
                $placed[] = ['w' => $w, 'clue' => $clue, 'r' => $r, 'c' => $c, 'dir' => $dir];
            }
            $pending = $rest;
        }
        if (!$pending) {
            return $placed;
        }
        if (!$best || count($placed) > count($best)) {
            $best = $placed;
        }
    }
    return $best;
}

$out = [];
foreach ($PUZZLES as $i => $p) {
    $S = (int) $p['size'];
    $placed = buildPuzzle($p['words'], $S);
    if (!$placed) {
        fwrite(STDERR, "Puzzle {$p['name']}: no se pudo colocar\n");
        continue;
    }
    // Recorta márgenes vacíos y numera los arranques (orden de lectura)
    $minR = $S; $minC = $S; $maxR = 0; $maxC = 0;
    foreach ($placed as $w) {
        $L = strlen($w['w']);
        [$dr, $dc] = $w['dir'] === 'h' ? [0, 1] : [1, 0];
        $minR = min($minR, $w['r']); $maxR = max($maxR, $w['r'] + $dr * ($L - 1));
        $minC = min($minC, $w['c']); $maxC = max($maxC, $w['c'] + $dc * ($L - 1));
    }
    foreach ($placed as &$w) {
        $w['r'] -= $minR;
        $w['c'] -= $minC;
    }
    unset($w);
    usort($placed, fn ($a, $b) => ($a['r'] <=> $b['r']) ?: ($a['c'] <=> $b['c']) ?: ($a['dir'] <=> $b['dir']));
    $nums = [];
    $n = 0;
    foreach ($placed as &$w) {
        $key = $w['r'] . ':' . $w['c'];
        if (!isset($nums[$key])) {
            $nums[$key] = ++$n;
        }
        $w['n'] = $nums[$key];
    }
    unset($w);

    $out[] = [
        'name' => $p['name'], 'icon' => $p['icon'],
        'rows' => $maxR - $minR + 1, 'cols' => $maxC - $minC + 1,
        'words' => array_map(fn ($w) => [
            'n' => $w['n'], 'w' => $w['w'], 'clue' => $w['clue'],
            'r' => $w['r'], 'c' => $w['c'], 'dir' => $w['dir'],
        ], $placed),
    ];
    echo "✅ {$p['name']}: ", count($placed), " palabras, grid ",
        $maxR - $minR + 1, "x", $maxC - $minC + 1, "\n";
    // vista previa
    $g = array_fill(0, $maxR - $minR + 1, array_fill(0, $maxC - $minC + 1, '.'));
    foreach ($placed as $w) {
        [$dr, $dc] = $w['dir'] === 'h' ? [0, 1] : [1, 0];
        for ($k = 0; $k < strlen($w['w']); $k++) {
            $g[$w['r'] + $dr * $k][$w['c'] + $dc * $k] = $w['w'][$k];
        }
    }
    foreach ($g as $row) {
        echo '   ', implode(' ', $row), "\n";
    }
}

file_put_contents(
    __DIR__ . '/../public/assets/games/crucigrama.json',
    json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n"
);
echo "\nEscrito public/assets/games/crucigrama.json (", count($out), " puzzles)\n";
