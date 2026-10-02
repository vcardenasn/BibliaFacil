<?php

namespace Biblia\Core;

/**
 * i18n mínimo — la clave ES el texto en español (idioma fuente del sitio).
 * lang/en.php mapea 'Texto en español' => 'English text'; sin traducción
 * registrada, t() devuelve el español intacto (nunca rompe la UI).
 *
 * Resolución de idioma (en index.php, antes de cualquier salida):
 *   ?lang=es|en → cookie bf_lang (1 año) → 'es' por defecto.
 */
final class I18n
{
    private const LANGS = ['es', 'en'];
    private static string $lang = 'es';
    /** @var array<string,string>|null */
    private static ?array $map = null;

    public static function lang(): string
    {
        return self::$lang;
    }

    public static function isEn(): bool
    {
        return self::$lang === 'en';
    }

    /** Detecta y fija el idioma; persiste con cookie cuando llega ?lang=. */
    public static function boot(): void
    {
        $req = (string) ($_GET['lang'] ?? '');
        if (in_array($req, self::LANGS, true)) {
            self::$lang = $req;
            if (!headers_sent()) {
                @setcookie('bf_lang', $req, [
                    'expires' => time() + 31536000, 'path' => '/',
                    'httponly' => false, 'samesite' => 'Lax',
                    'secure' => !empty($_SERVER['HTTPS']),
                ]);
            }
            return;
        }
        $ck = (string) ($_COOKIE['bf_lang'] ?? '');
        self::$lang = in_array($ck, self::LANGS, true) ? $ck : 'es';
    }

    public static function t(string $es): string
    {
        if (self::$lang === 'es') {
            return $es;
        }
        if (self::$map === null) {
            $file = BASE_PATH . '/lang/' . self::$lang . '.php';
            self::$map = is_file($file) ? (array) require $file : [];
        }
        return self::$map[$es] ?? $es;
    }

    /** Mapa completo para JS (window.BF_T). En 'es' devuelve [] — el JS
     *  usa el literal español como fallback y no necesita el mapa. */
    public static function all(): array
    {
        if (self::$lang === 'es') {
            return [];
        }
        self::t(''); // fuerza la carga del mapa
        return self::$map ?? [];
    }

    /** Mapa para JS: solo las claves pedidas, traducidas al idioma activo. */
    public static function jsMap(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = self::t($k);
        }
        return $out;
    }

    /** URL del idioma alternativo conservando la ruta actual (sin ?lang viejo). */
    public static function switchUrl(string $target): string
    {
        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        parse_str((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY), $q);
        $q['lang'] = $target;
        return $path . '?' . http_build_query($q);
    }
}

