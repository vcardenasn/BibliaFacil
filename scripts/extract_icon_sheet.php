<?php
/**
 * Extrae cada figura aislada de una lámina de íconos (fondo blanco) a PNGs
 * individuales con transparencia.
 *
 * Uso: php scripts/extract_icon_sheet.php <lámina.jpg> <dir_salida>
 *
 * 1. Detección a baja resolución: componentes conexos de píxeles no-blancos.
 * 2. Recorte del original a resolución completa con margen.
 * 3. Píxeles casi blancos → transparentes.
 * 4. Salida: <dir_salida>/item-NN.png ordenados en lectura (fila por fila).
 *
 * Origen de la lámina: set de arca de Noé (Freepik, requiere licencia Premium
 * del usuario o atribución a Freepik si es descarga gratuita).
 */

if ($argc < 3) {
    fwrite(STDERR, "Uso: php extract_icon_sheet.php <hoja> <salida>\n");
    exit(1);
}
[$_, $srcPath, $outDir] = $argv;

$src = imagecreatefromjpeg($srcPath);
$W = imagesx($src);
$H = imagesy($src);

// --- Paso 1: detección a 1/4 de resolución ---------------------------
$scale = 4;
$w = intdiv($W, $scale);
$h = intdiv($H, $scale);
$small = imagecreatetruecolor($w, $h);
imagecopyresampled($small, $src, 0, 0, 0, 0, $w, $h, $W, $H);

$fg = array_fill(0, $h, ''); // bitmap de foreground como strings de bits
for ($y = 0; $y < $h; $y++) {
    $row = '';
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($small, $x, $y);
        $r = ($rgb >> 16) & 255;
        $g = ($rgb >> 8) & 255;
        $b = $rgb & 255;
        // foreground: suficientemente lejos del blanco
        $row .= (255 - min($r, $g, $b) > 45) ? '1' : '0';
    }
    $fg[$y] = $row;
}

// BFS de componentes conexos
$seen = [];
$boxes = [];
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        if ($fg[$y][$x] === '0' || isset($seen["$x,$y"])) {
            continue;
        }
        $stack = [[$x, $y]];
        $seen["$x,$y"] = true;
        $minX = $maxX = $x;
        $minY = $maxY = $y;
        $count = 0;
        while ($stack) {
            [$cx, $cy] = array_pop($stack);
            $count++;
            if ($cx < $minX) { $minX = $cx; }
            if ($cx > $maxX) { $maxX = $cx; }
            if ($cy < $minY) { $minY = $cy; }
            if ($cy > $maxY) { $maxY = $cy; }
            foreach ([[1,0],[-1,0],[0,1],[0,-1]] as [$dx, $dy]) {
                $nx = $cx + $dx;
                $ny = $cy + $dy;
                if ($nx < 0 || $ny < 0 || $nx >= $w || $ny >= $h
                    || isset($seen["$nx,$ny"]) || $fg[$ny][$nx] === '0') {
                    continue;
                }
                $seen["$nx,$ny"] = true;
                $stack[] = [$nx, $ny];
            }
        }
        // descarta ruido pequeño (menos de ~1200px² en el original)
        if ($count >= 75) {
            $boxes[] = [$minX * $scale, $minY * $scale, $maxX * $scale, $maxY * $scale, $count];
        }
    }
}


// Orden de lectura: por filas, luego izquierda→derecha
usort($boxes, function ($a, $b) {
    $rowA = intdiv($a[1], 800);
    $rowB = intdiv($b[1], 800);
    return $rowA === $rowB ? $a[0] <=> $b[0] : $a[1] <=> $b[1];
});

printf("%d objetos detectados\n", count($boxes));
if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

// --- Paso 2/3: recorte a resolución completa + fondo transparente ----
$pad = 24;
foreach ($boxes as $n => [$x0, $y0, $x1, $y1]) {
    $x0 = max(0, $x0 - $pad);
    $y0 = max(0, $y0 - $pad);
    $x1 = min($W - 1, $x1 + $pad);
    $y1 = min($H - 1, $y1 + $pad);
    $cw = $x1 - $x0 + 1;
    $ch = $y1 - $y0 + 1;

    $crop = imagecreatetruecolor($cw, $ch);
    imagealphablending($crop, false);
    imagesavealpha($crop, true);
    $trans = imagecolorallocatealpha($crop, 255, 255, 255, 127);
    imagefill($crop, 0, 0, $trans);
    imagecopy($crop, $src, 0, 0, $x0, $y0, $cw, $ch);

    for ($y = 0; $y < $ch; $y++) {
        for ($x = 0; $x < $cw; $x++) {
            $rgb = imagecolorat($crop, $x, $y);
            $r = ($rgb >> 16) & 255;
            $g = ($rgb >> 8) & 255;
            $b = $rgb & 255;
            if ($r > 242 && $g > 242 && $b > 242) {
                imagesetpixel($crop, $x, $y, $trans);
            }
        }
    }
    $name = sprintf('%s/item-%02d.png', $outDir, $n + 1);
    imagepng($crop, $name, 7);
    printf("%s  %dx%d\n", $name, $cw, $ch);

}

