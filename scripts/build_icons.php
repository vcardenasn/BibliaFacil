<?php

// EPIC 08 / US-081 — genera los íconos PWA (public/assets/icons/*.png).
// Ícono: cruz blanca sobre degradado índigo (marca ✝ del header).
// Uso: php scripts/build_icons.php — idempotente, se commitea el resultado.

$dir = dirname(__DIR__) . '/public/assets/icons';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$draw = function (int $size, float $pad, string $file): void {
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, true);
    imagesavealpha($img, true);

    // Degradado vertical índigo → índigo profundo
    $top = [0x3f, 0x63, 0xb4];
    $bot = [0x2e, 0x4a, 0x8a];
    for ($y = 0; $y < $size; $y++) {
        $f = $y / $size;
        $c = imagecolorallocate(
            $img,
            (int) round($top[0] + ($bot[0] - $top[0]) * $f),
            (int) round($top[1] + ($bot[1] - $top[1]) * $f),
            (int) round($top[2] + ($bot[2] - $top[2]) * $f)
        );
        imageline($img, 0, $y, $size, $y, $c);
    }

    // Cruz latina centrada; $pad encoge la zona útil (maskable)
    $white = imagecolorallocate($img, 0xff, 0xff, 0xff);
    $gold = imagecolorallocate($img, 0xc9, 0xa2, 0x27);
    $cx = $size / 2;
    $th = (int) round($size * 0.13);
    $top2 = $size * (0.20 + $pad * 0.5);
    $bottom = $size * (0.80 - $pad * 0.5);
    $crossY = $size * (0.42 + $pad * 0.25);
    $arm = $size * (0.62 - $pad) / 2;

    imagefilledrectangle($img, (int) ($cx - $th / 2 + 2), (int) ($top2 + 2), (int) ($cx + $th / 2 + 2), (int) ($bottom + 2), $gold);
    imagefilledrectangle($img, (int) ($cx - $arm + 2), (int) ($crossY - $th / 2 + 2), (int) ($cx + $arm + 2), (int) ($crossY + $th / 2 + 2), $gold);
    imagefilledrectangle($img, (int) ($cx - $th / 2), (int) $top2, (int) ($cx + $th / 2), (int) $bottom, $white);
    imagefilledrectangle($img, (int) ($cx - $arm), (int) ($crossY - $th / 2), (int) ($cx + $arm), (int) ($crossY + $th / 2), $white);

    imagepng($img, $file, 6);
    echo "{$file}\n";
};

$draw(192, 0.0, "$dir/icon-192.png");
$draw(512, 0.0, "$dir/icon-512.png");
$draw(512, 0.2, "$dir/icon-maskable.png"); // margen de seguridad para recorte
$draw(180, 0.0, "$dir/apple-touch-icon.png");
echo "Íconos generados en {$dir}\n";
