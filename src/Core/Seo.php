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
            'noindex' => in_array($view, ['search', 'mias', 'comparar', 'notfound', 'error'], true),
            'ogType' => 'website',
            'locale' => 'es_LA',
            'htmlLang' => 'es',
            'image' => null,
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
            case 'home':
                $meta['desc'] = 'Lee la Biblia en línea gratis y sin anuncios. Encuentra versículos, explora temas y aprende con juegos bíblicos, sin crear una cuenta.';
                $meta['canonical'] = self::abs('/');
                $meta['jsonld'] = [self::websiteLd()];
                $votd = $data['votd'] ?? null;
                $votdVersion = $data['votdVersion'] ?? null;
                if ($votd && $votdVersion) {
                    $meta['image'] = self::abs("img/{$votdVersion['code']}/{$votd['book_slug']}/{$votd['chapter']}/{$votd['verse']}");
                }
                break;

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

            case 'comparar':
                $cb = $data['book'] ?? null;
                if ($cb && !empty($data['va']) && !empty($data['vb'])) {
                    $meta['desc'] = "Compara {$cb['name']} {$data['chapter']} entre {$data['va']['name']} y {$data['vb']['name']}, versículo a versículo.";
                    $meta['canonical'] = self::abs("comparar/{$cb['slug']}/{$data['chapter']}/{$data['va']['code']}/{$data['vb']['code']}");
                    $meta['crumbs'] = self::pageCrumbs('Comparar', "{$cb['name']} {$data['chapter']}", 'comparar');
                } else {
                    $meta['desc'] = 'Compara dos versiones de la Biblia lado a lado, versículo por versículo.';
                    $meta['canonical'] = self::abs('comparar');
                    $meta['crumbs'] = self::pageCrumbs('Comparar');
                }
                break;

            case 'planes':
                $meta['desc'] = 'Planes de lectura bíblica gratis y sin cuenta: la Biblia en un año, el Nuevo Testamento en 90 días, Salmos y Proverbios en un mes.';
                $meta['crumbs'] = self::pageCrumbs('Planes de lectura');
                $meta['jsonld'] = [self::breadcrumbLd($meta['crumbs'])];
                break;

            case 'plan':
                $p = $data['plan'] ?? [];
                $meta['desc'] = ($p['desc'] ?? 'Plan de lectura bíblica.') . ' Progreso guardado en tu dispositivo.';
                $meta['crumbs'] = self::pageCrumbs('Planes de lectura', $p['name'] ?? null, 'planes');
                $meta['jsonld'] = [self::breadcrumbLd($meta['crumbs']), [
                    '@context' => 'https://schema.org',
                    '@type' => 'ItemList',
                    'name' => $p['name'] ?? 'Plan de lectura',
                    'numberOfItems' => count($data['days'] ?? []),
                ]];
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

            case 'temas':
                $meta['desc'] = 'Versículos de la Biblia por tema — amor, fe, ánimo, paz, familia y más, listos para leer y compartir.';
                $meta['crumbs'] = self::pageCrumbs('Temas');
                break;

            case 'tema':
                $t = $data['tema'] ?? [];
                $meta['desc'] = ($t['desc'] ?? 'Versículos por tema') . ' Colección curada en Biblia Fácil.';
                $meta['crumbs'] = self::pageCrumbs('Temas', $t['name'] ?? null, 'temas');
                $meta['jsonld'] = [self::breadcrumbLd($meta['crumbs']), [
                    '@context' => 'https://schema.org',
                    '@type' => 'ItemList',
                    'name' => 'Versículos de ' . ($t['name'] ?? ''),
                    'numberOfItems' => count($data['verses'] ?? []),
                ]];
                break;

            case 'versiculo':
                $en2 = $data['entry'] ?? [];
                $meta['desc'] = ($en2['context'] ?? '') . ' Léelo en varias versiones en Biblia Fácil.';
                $meta['ogType'] = 'article';
                // og:image con la versión por defecto (US-200)
                if (!empty($data['texts'][0])) {
                    $t0 = $data['texts'][0];
                    $meta['image'] = self::abs("img/{$t0['code']}/{$t0['book_slug']}/{$t0['chapter']}/{$t0['verse']}");
                }
                $meta['crumbs'] = self::pageCrumbs('Versículos', $en2['title'] ?? null, 'versiculo');
                $meta['jsonld'] = [self::breadcrumbLd($meta['crumbs']), [
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => ($en2['title'] ?? '') . ' — texto y significado',
                    'inLanguage' => 'es',
                    'isAccessibleForFree' => true,
                ]];
                break;

            case 'votd':
                $vt = $data['votd'] ?? null;
                $meta['desc'] = $vt
                    ? '"' . mb_substr(strip_tags((string) $vt['text']), 0, 120) . '…" — ' . $vt['book_name'] . ' ' . $vt['chapter'] . ':' . $vt['verse']
                    : 'Un versículo de la Biblia cada día.';
                $meta['crumbs'] = self::pageCrumbs('Versículo del día');
                break;

            case 'shareverse':
                $sv = $data['verse'] ?? [];
                $meta['desc'] = '"' . mb_substr(strip_tags((string) ($sv['text'] ?? '')), 0, 140) . '…" — ' . ($data['ref'] ?? '');
                $meta['ogType'] = 'article';
                $meta['image'] = $data['imgUrl'] ?? null;
                $meta['crumbs'] = self::pageCrumbs('Versículo', $data['ref'] ?? null);
                $meta['jsonld'] = [[
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => ($data['ref'] ?? '') . ' — ' . (($data['version'] ?? [])['name'] ?? ''),
                    'inLanguage' => ($data['version'] ?? [])['language'] === 'en' ? 'en' : 'es',
                    'isAccessibleForFree' => true,
                ]];
                break;

            case 'guias':

            case 'guia':
                $g = $data['guia'] ?? [];
                $meta['desc'] = ($g['desc'] ?? '') . ' — guía de Biblia Fácil.';
                $meta['crumbs'] = self::pageCrumbs('Guías', $g['title'] ?? null, 'guias');
                $meta['jsonld'] = [self::breadcrumbLd($meta['crumbs'])];
                break;
        }

        return $meta;
    }

    /** Breadcrumbs para páginas no-versionadas: Inicio › Sección › Página. */
    private static function pageCrumbs(string $section, ?string $current = null, string $sectionUrl = ''): array
    {
        $c = [['label' => 'Inicio', 'url' => url('/')]];
        if ($current === null) {
            $c[] = ['label' => $section, 'url' => null];
        } else {
            $c[] = ['label' => $section, 'url' => $sectionUrl === '' ? null : url($sectionUrl)];
            $c[] = ['label' => $current, 'url' => null];
        }
        return $c;
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
