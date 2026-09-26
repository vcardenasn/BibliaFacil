<?php

namespace Biblia\Core;

/**
 * SEO técnico (EPIC 18): meta dinámico por vista — description, canonical,
 * Open Graph/Twitter, hreflang es↔en, JSON-LD y breadcrumbs.
 * view() lo invoca por cada página; todo sale de los datos ya cargados.
 */
final class Seo
{
    /**
     * URL absoluta para un path relativo.
     * Deriva el dominio del Host de la petición (transparente si el dominio
     * cambia — soporta proxies vía X-Forwarded-*); `APP_URL` es respaldo CLI.
     */
    public static function abs(string $path = ''): string
    {
        $host = (string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '');
        $host = explode(',', $host)[0];
        if (trim($host) !== '' && preg_match('/^[a-z0-9.\-]+(:\d+)?$/i', trim($host))) {
            $proto = (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
            if ($proto === '') {
                $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            }
            return $proto . '://' . trim($host) . url($path);
        }
        $base = rtrim((string) config('app.url', ''), '/');
        return ($base === '' ? 'http://localhost' : $base) . url($path);
    }

    /**
     * Construye el paquete de meta para el layout.
     * @return array{desc:string,canonical:string,noindex:bool,ogType:string,
     *   locale:string,htmlLang:string,hreflang:array,jsonld:array,crumbs:array}
     */
    public static function build(string $view, array $data): array
    {
        $meta = [
            'desc' => 'Lee la Biblia en múltiples versiones, fácil y rápido.',
            'canonical' => self::abs((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)),
            'noindex' => in_array($view, ['search', 'mias', 'notfound', 'error'], true),
            'ogType' => 'website',
            'locale' => 'es_LA',
            'htmlLang' => 'es',
            'hreflang' => [],
            'jsonld' => [],
            'crumbs' => [],
        ];

        $version = $data['version'] ?? null;
        $versions = $data['versions'] ?? [];
        $en = $version && ($version['language'] ?? 'es') === 'en';
        if ($en) {
            $meta['locale'] = 'en_US';
            $meta['htmlLang'] = 'en';
        }

        switch ($view) {
            case 'reader':
                $meta['desc'] = self::chapterDesc($data['verses'] ?? []);
                $meta['ogType'] = 'article';
                $meta['crumbs'] = self::crumbs($version, $data['book'] ?? null, $data['chapter'] ?? null);
                $meta['hreflang'] = self::hreflang($version, $versions, "{$version['code']}/{$data['book']['slug']}/{$data['chapter']}");
                $meta['jsonld'] = [
                    self::breadcrumbLd($meta['crumbs']),
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'Article',
                        'headline' => "{$data['book']['name']} {$data['chapter']} — {$version['name']}",
                        'isPartOf' => ['@type' => 'Book', 'name' => "Biblia {$version['name']}", 'bookEdition' => $data['book']['name']],
                        'inLanguage' => $meta['htmlLang'],
                        'isAccessibleForFree' => true,
                    ],
                ];
                break;

            case 'chapters':
                $book = $data['book'] ?? null;
                $meta['desc'] = $book ? "Índice de capítulos de {$book['name']} ({$book['chapters']} capítulos) — {$version['name']}." : $meta['desc'];
                $meta['crumbs'] = self::crumbs($version, $book, null);
                $meta['hreflang'] = self::hreflang($version, $versions, "{$version['code']}/{$book['slug']}");
                $meta['jsonld'] = [self::breadcrumbLd($meta['crumbs'])];
                break;

            case 'books':
                $meta['desc'] = $version ? "Lee la Biblia {$version['name']} en línea, gratis — los 66 libros con búsqueda y audio." : $meta['desc'];
                $meta['crumbs'] = self::crumbs($version, null, null);
                $meta['hreflang'] = self::hreflang($version, $versions, (string) $version['code']);
                $meta['jsonld'] = [self::breadcrumbLd($meta['crumbs']), self::websiteLd()];
                break;

            case 'juegos':
                $meta['desc'] = 'Juegos bíblicos gratis para niños — trivia, memoria, ordena la historia, completa el versículo y más.';
                break;

            case 'juego':
                $g = $data['game'] ?? [];
                $meta['desc'] = "Juega {$g['name']} gratis — juego bíblico para niños en Biblia Fácil.";
                break;

            case 'search':
                $meta['desc'] = 'Busca cualquier versículo o palabra en la Biblia.';
                break;
        }

        return $meta;
    }

    /** Descripción del capítulo: primeras ~155 letras de los primeros versículos. */
    private static function chapterDesc(array $verses): string
    {
        $t = '';
        foreach (array_slice($verses, 0, 3) as $v) {
            $t .= ($t === '' ? '' : ' ') . trim(strip_tags((string) $v['text']));
            if (mb_strlen($t) >= 155) {
                break;
            }
        }
        return $t === '' ? 'Lee este capítulo de la Biblia.' : mb_substr($t, 0, 155) . (mb_strlen($t) > 155 ? '…' : '');
    }

    /** Breadcrumbs: Versión › Libro › Capítulo (último sin URL). */
    private static function crumbs(?array $version, ?array $book, ?int $chapter): array
    {
        $c = [['label' => 'Inicio', 'url' => url('/')]];
        if ($version) {
            $c[] = ['label' => $version['name'], 'url' => url((string) $version['code'])];
        }
        if ($book) {
            $c[] = ['label' => $book['name'], 'url' => url("{$version['code']}/{$book['slug']}")];
        }
        if ($book && $chapter) {
            $c[] = ['label' => "Capítulo {$chapter}", 'url' => null];
        }
        return $c;
    }

    /**
     * hreflang es↔en: cada página de versión enlaza su equivalente en el otro
     * idioma (KJV ↔ versión española por defecto) + x-default.
     */
    private static function hreflang(?array $version, array $versions, string $path): array
    {
        if (!$version) {
            return [];
        }
        $codes = array_column($versions, 'code');
        $rest = preg_replace('/^' . preg_quote((string) $version['code'], '/') . '\/?/', '', $path);
        $defEs = config('app.default_version', 'rvr1909');
        $links = [($version['language'] ?? 'es') === 'en' ? 'en' : 'es' => self::abs($path)];
        if (($version['language'] ?? 'es') === 'en' && in_array($defEs, $codes, true)) {
            $links['es'] = self::abs($defEs . ($rest === '' ? '' : "/{$rest}"));
        } elseif (in_array('kjv', $codes, true)) {
            $links['en'] = self::abs('kjv' . ($rest === '' ? '' : "/{$rest}"));
        }
        $links['x-default'] = self::abs($defEs . ($rest === '' ? '' : "/{$rest}"));
        return $links;
    }

    private static function breadcrumbLd(array $crumbs): array
    {
        $items = [];
        foreach ($crumbs as $i => $c) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['label']]
                + ($c['url'] ? ['item' => self::abs(ltrim($c['url'], '/') === '' ? '/' : ltrim($c['url'], '/'))] : []);
        }
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    /** WebSite + SearchAction → habilita caja de búsqueda en resultados de Google. */
    private static function websiteLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Biblia Fácil',
            'url' => self::abs('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => self::abs('buscar') . '?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }
}
