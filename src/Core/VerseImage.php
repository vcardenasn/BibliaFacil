<?php

namespace Biblia\Core;

/**
 * og:image dinámico (US-200): PNG 1200×630 con el versículo sobre gradiente.
 * Fuente: assets/fonts/EBGaramond.ttf (OFL). Cachea en storage/cache/img/.
 * Si GD no existe (host raro) devuelve null — la página sigue funcionando.
 */
final class VerseImage
{
    private const W = 1200;
    private const H = 630;

    public static function supported(): bool
    {
        return extension_loaded('gd') && function_exists('imagettftext')
            && is_file(BASE_PATH . '/assets/fonts/EBGaramond.ttf');
    }

    /** Genera (o lee de caché) el PNG y devuelve sus bytes. */
    public static function png(string $verseText, string $ref, string $key): ?string
    {
        if (!self::supported()) {
            return null;
        }
        $cache = STORAGE_PATH . '/cache/img/' . preg_replace('/[^a-z0-9_-]/i', '', $key . '-' . self::domain()) . '.png';
        if (is_file($cache)) {
            return file_get_contents($cache);
        }
        $im = self::canvas();
        self::decorate($im);
        self::text($im, $verseText, $ref);
        ob_start();
        imagepng($im, null, 6);
        $bytes = ob_get_clean();
        if (!is_dir(dirname($cache))) {
            @mkdir(dirname($cache), 0755, true);
        }
        @file_put_contents($cache, $bytes);
        return $bytes;
    }

    /** Dominio actual para el pie (transparente — del Host de la petición). */
    private static function domain(): string
    {
        $host = (string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '');
        $host = trim(explode(',', $host)[0]);
        return $host !== '' ? $host : (string) parse_url((string) config('app.url', ''), PHP_URL_HOST);
    }

    private static function canvas(): \GdImage
    {
        $im = imagecreatetruecolor(self::W, self::H);
        // Gradiente vertical índigo profundo → violeta
        [$r1, $g1, $b1] = [49, 46, 129];
        [$r2, $g2, $b2] = [124, 58, 237];
        for ($y = 0; $y < self::H; $y++) {
            $t = $y / self::H;
            $c = imagecolorallocate(
                $im,
                (int) ($r1 + ($r2 - $r1) * $t),
                (int) ($g1 + ($g2 - $g1) * $t),
                (int) ($b1 + ($b2 - $b1) * $t)
            );
            imageline($im, 0, $y, self::W, $y, $c);
        }
        return $im;
    }

    private static function decorate(\GdImage $im): void
    {
        // Cruz dorada semitransparente arriba (la fuente no trae ✝)
        $gold = imagecolorallocatealpha($im, 251, 191, 36, 96);
        $cx = (int) (self::W / 2);
        imagefilledrectangle($im, $cx - 8, 30, $cx + 8, 100, $gold);
        imagefilledrectangle($im, $cx - 32, 48, $cx + 32, 62, $gold);
        // línea dorada fina bajo el versículo
        $line = imagecolorallocatealpha($im, 251, 191, 36, 70);
        imageline($im, (int) (self::W / 2 - 120), self::H - 175, (int) (self::W / 2 + 120), self::H - 175, $line);
    }

    private static function text(\GdImage $im, string $verse, string $ref): void
    {
        $font = self::font();
        $white = imagecolorallocate($im, 255, 255, 255);
        $gold = imagecolorallocate($im, 251, 191, 36);
        $dim = imagecolorallocatealpha($im, 255, 255, 255, 40);

        $verse = '"' . trim(preg_replace('/\\s+/u', ' ', (string) $verse), ' "\'«»“”„‚') . '"';
        // Auto-ajuste: baja el tamaño hasta que quepa en ~1000px × 300px
        $size = 46;
        do {
            $lines = self::wrap($verse, $font, $size, 1000);
            $lh = (int) ($size * 1.35);
            if (count($lines) * $lh <= 300 || $size <= 24) {
                break;
            }
            $size -= 4;
        } while ($size > 24);
        $y = (int) (150 + (300 - count($lines) * $lh) / 2);
        foreach ($lines as $line) {
            $bb = imagettfbbox($size, 0, $font, $line);
            $w = $bb[2] - $bb[0];
            imagettftext($im, $size, 0, (int) ((self::W - $w) / 2), $y, $white, $font, $line);
            $y += $lh;
        }
        // Referencia en dorado
        $rs = 34;
        $bb = imagettfbbox($rs, 0, $font, $ref);
        imagettftext($im, $rs, 0, (int) ((self::W - ($bb[2] - $bb[0])) / 2), self::H - 110, $gold, $font, $ref);
        // Dominio abajo, discreto
        $dom = self::domain();
        if ($dom !== '') {
            $ds = 22;
            $bb = imagettfbbox($ds, 0, $font, $dom);
            imagettftext($im, $ds, 0, (int) ((self::W - ($bb[2] - $bb[0])) / 2), self::H - 55, $dim, $font, $dom);
        }
    }

    /** Word-wrap medido con imagettfbbox. */
    private static function wrap(string $text, string $font, int $size, int $maxW): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $cur = '';
        foreach ($words as $w) {
            $try = $cur === '' ? $w : $cur . ' ' . $w;
            $bb = imagettfbbox($size, 0, $font, $try);
            if ($bb[2] - $bb[0] > $maxW && $cur !== '') {
                $lines[] = $cur;
                $cur = $w;
            } else {
                $cur = $try;
            }
        }
        if ($cur !== '') {
            $lines[] = $cur;
        }
        return $lines;
    }

    private static function font(): string
    {
        return BASE_PATH . '/assets/fonts/EBGaramond.ttf';
    }
}
