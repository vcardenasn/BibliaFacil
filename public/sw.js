// Palabra Fiel — service worker (EPIC 08 / US-080).
// install: precache del shell + los 11 juegos completos (código, datos e
// imágenes) → los juegos funcionan 100% sin internet. Páginas: network-first
// con caché de lo visitado. Assets: stale-while-revalidate.
// Nunca cachea /api, /track.php ni POST.
var VERSION = 'bf-v2';
var PAGES = VERSION + '-pages';
var ASSETS = VERSION + '-assets';
var COREC = VERSION + '-core';
var MAX_PAGES = 80;

// Núcleo: home, hub y páginas de juego + CSS/JS/JSON — bloquea install
var PRECACHE_CORE = [
        '/',
        '/juegos',
        '/juegos/vf',
        '/juegos/paloma',
        '/juegos/trivia',
        '/juegos/versiculo',
        '/juegos/historia',
        '/juegos/memory',
        '/juegos/david',
        '/juegos/libros',
        '/juegos/personaje',
        '/juegos/sopa',
        '/juegos/crucigrama',
        '/assets/app.css',
        '/assets/app.js',
        '/assets/sw-register.js',
        '/assets/juegos.css',
        '/assets/juegos.js',
        '/assets/juego-crucigrama.js',
        '/assets/juego-david.js',
        '/assets/juego-historia.js',
        '/assets/juego-libros.js',
        '/assets/juego-memory.js',
        '/assets/juego-paloma.js',
        '/assets/juego-personaje.js',
        '/assets/juego-sopa.js',
        '/assets/juego-trivia.js',
        '/assets/juego-versiculo.js',
        '/assets/juego-vf.js',
        '/assets/games/crucigrama.json',
        '/assets/games/historia.json',
        '/assets/games/libros.json',
        '/assets/games/memory.json',
        '/assets/games/personajes.json',
        '/assets/games/sopa.json',
        '/assets/games/trivia.json',
        '/assets/games/vf.json'
    ];

// Imágenes de juegos (covers, cartas, omoji, arkset): segundo plano, no bloquean
var PRECACHE_EXTRA = [
        '/assets/covers/cruc-alimentos.jpg',
        '/assets/covers/cruc-amigos-jesus.jpg',
        '/assets/covers/cruc-animales.jpg',
        '/assets/covers/cruc-apostoles.jpg',
        '/assets/covers/cruc-arca.jpg',
        '/assets/covers/cruc-creacion.jpg',
        '/assets/covers/cruc-exodo.jpg',
        '/assets/covers/cruc-fruto.jpg',
        '/assets/covers/cruc-genesis.jpg',
        '/assets/covers/cruc-jesus.jpg',
        '/assets/covers/cruc-libros-at.jpg',
        '/assets/covers/cruc-lugares.jpg',
        '/assets/covers/cruc-milagros.jpg',
        '/assets/covers/cruc-mujeres.jpg',
        '/assets/covers/cruc-navidad.jpg',
        '/assets/covers/cruc-oracion.jpg',
        '/assets/covers/cruc-pablo.jpg',
        '/assets/covers/cruc-palabras-fe.jpg',
        '/assets/covers/cruc-parabolas.jpg',
        '/assets/covers/cruc-personajes.jpg',
        '/assets/covers/cruc-profetas.jpg',
        '/assets/covers/cruc-resurreccion.jpg',
        '/assets/covers/cruc-reyes.jpg',
        '/assets/covers/cruc-salmos.jpg',
        '/assets/bibleimg/eden.jpg',
        '/assets/bibleimg/mem-arca.jpg',
        '/assets/bibleimg/mem-corona.jpg',
        '/assets/bibleimg/mem-daniel.jpg',
        '/assets/bibleimg/mem-david.jpg',
        '/assets/bibleimg/mem-escalera.jpg',
        '/assets/bibleimg/mem-ester.jpg',
        '/assets/bibleimg/mem-estrella.jpg',
        '/assets/bibleimg/mem-fuego.jpg',
        '/assets/bibleimg/mem-fuerza.jpg',
        '/assets/bibleimg/mem-jacob.jpg',
        '/assets/bibleimg/mem-jerico.jpg',
        '/assets/bibleimg/mem-jesus-nino.jpg',
        '/assets/bibleimg/mem-jesus.jpg',
        '/assets/bibleimg/mem-jonas.jpg',
        '/assets/bibleimg/mem-leon.jpg',
        '/assets/bibleimg/mem-mar.jpg',
        '/assets/bibleimg/mem-noe.jpg',
        '/assets/bibleimg/mem-oveja.jpg',
        '/assets/bibleimg/mem-panes.jpg',
        '/assets/bibleimg/mem-pastor.jpg',
        '/assets/bibleimg/mem-peces.jpg',
        '/assets/bibleimg/mem-pez.jpg',
        '/assets/bibleimg/mem-piedra.jpg',
        '/assets/bibleimg/mem-sanson.jpg',
        '/assets/bibleimg/mem-serpiente.jpg',
        '/assets/bibleimg/mem-trompetas.jpg',
        '/assets/bibleimg/mem-zarza.jpg',
        '/assets/omoji/1F0CF.svg',
        '/assets/omoji/1F305.svg',
        '/assets/omoji/1F308.svg',
        '/assets/omoji/1F30A.svg',
        '/assets/omoji/1F30D.svg',
        '/assets/omoji/1F311.svg',
        '/assets/omoji/1F31F.svg',
        '/assets/omoji/1F327.svg',
        '/assets/omoji/1F331.svg',
        '/assets/omoji/1F333.svg',
        '/assets/omoji/1F335.svg',
        '/assets/omoji/1F33E.svg',
        '/assets/omoji/1F347.svg',
        '/assets/omoji/1F34E.svg',
        '/assets/omoji/1F35E.svg',
        '/assets/omoji/1F36F.svg',
        '/assets/omoji/1F381.svg',
        '/assets/omoji/1F389.svg',
        '/assets/omoji/1F3AC.svg',
        '/assets/omoji/1F3AD.svg',
        '/assets/omoji/1F3AE.svg',
        '/assets/omoji/1F3AF.svg',
        '/assets/omoji/1F3B2.svg',
        '/assets/omoji/1F3B5.svg',
        '/assets/omoji/1F3BA.svg',
        '/assets/omoji/1F3C1.svg',
        '/assets/omoji/1F3C5.svg',
        '/assets/omoji/1F3C6.svg',
        '/assets/omoji/1F3D6.svg',
        '/assets/omoji/1F3DB.svg',
        '/assets/omoji/1F3F0.svg',
        '/assets/omoji/1F3FA.svg',
        '/assets/omoji/1F40D.svg',
        '/assets/omoji/1F411.svg',
        '/assets/omoji/1F418.svg',
        '/assets/omoji/1F41F.svg',
        '/assets/omoji/1F426.svg',
        '/assets/omoji/1F433.svg',
        '/assets/omoji/1F434.svg',
        '/assets/omoji/1F442.svg',
        '/assets/omoji/1F451.svg',
        '/assets/omoji/1F466.svg',
        '/assets/omoji/1F469.svg',
        '/assets/omoji/1F46B.svg',
        '/assets/omoji/1F476.svg',
        '/assets/omoji/1F483.svg',
        '/assets/omoji/1F487.svg',
        '/assets/omoji/1F48E.svg',
        '/assets/omoji/1F4A1.svg',
        '/assets/omoji/1F4AA.svg',
        '/assets/omoji/1F4C5.svg',
        '/assets/omoji/1F4D6.svg',
        '/assets/omoji/1F4D7.svg',
        '/assets/omoji/1F4DA.svg',
        '/assets/omoji/1F4DC.svg',
        '/assets/omoji/1F4E2.svg',
        '/assets/omoji/1F4E3.svg',
        '/assets/omoji/1F50D.svg',
        '/assets/omoji/1F512.svg',
        '/assets/omoji/1F525.svg',
        '/assets/omoji/1F528.svg',
        '/assets/omoji/1F54A.svg',
        '/assets/omoji/1F573.svg',
        '/assets/omoji/1F590.svg',
        '/assets/omoji/1F5E3.svg',
        '/assets/omoji/1F607.svg',
        '/assets/omoji/1F60C.svg',
        '/assets/omoji/1F620.svg',
        '/assets/omoji/1F634.svg',
        '/assets/omoji/1F64F.svg',
        '/assets/omoji/1F6A2.svg',
        '/assets/omoji/1F6B6.svg',
        '/assets/omoji/1F914.svg',
        '/assets/omoji/1F917.svg',
        '/assets/omoji/1F947.svg',
        '/assets/omoji/1F948.svg',
        '/assets/omoji/1F949.svg',
        '/assets/omoji/1F981.svg',
        '/assets/omoji/1F9C0.svg',
        '/assets/omoji/1F9D1-200D-1F33E.svg',
        '/assets/omoji/1F9D4.svg',
        '/assets/omoji/1F9E0.svg',
        '/assets/omoji/1F9E5.svg',
        '/assets/omoji/1F9E9.svg',
        '/assets/omoji/1F9ED.svg',
        '/assets/omoji/1F9F1.svg',
        '/assets/omoji/1FA9C.svg',
        '/assets/omoji/1FAA6.svg',
        '/assets/omoji/1FAA8.svg',
        '/assets/omoji/1FACF.svg',
        '/assets/omoji/2694.svg',
        '/assets/omoji/2709.svg',
        '/assets/omoji/271D.svg',
        '/assets/omoji/2728.svg',
        '/assets/omoji/2753.svg',
        '/assets/omoji/2B50.svg',
        '/assets/arkset/adan.png',
        '/assets/arkset/anciano-orando.png',
        '/assets/arkset/anciano.png',
        '/assets/arkset/arca-animales.png',
        '/assets/arkset/arca.png',
        '/assets/arkset/arca2.png',
        '/assets/arkset/burro.png',
        '/assets/arkset/cain-abel.png',
        '/assets/arkset/cerdo.png',
        '/assets/arkset/cerdo2.png',
        '/assets/arkset/crucifixion.png',
        '/assets/arkset/david-goliat.png',
        '/assets/arkset/elefante.png',
        '/assets/arkset/elefante2.png',
        '/assets/arkset/eva-eden.png',
        '/assets/arkset/eva.png',
        '/assets/arkset/gacela.png',
        '/assets/arkset/hombre-cayendo.png',
        '/assets/arkset/jesus-ascenso.png',
        '/assets/arkset/jirafa.png',
        '/assets/arkset/jonas-agua.png',
        '/assets/arkset/jonas-rezando.png',
        '/assets/arkset/jonas.png',
        '/assets/arkset/leon.png',
        '/assets/arkset/leona.png',
        '/assets/arkset/luz-divina.png',
        '/assets/arkset/moises-mar.png',
        '/assets/arkset/moises-tablas.png',
        '/assets/arkset/mono.png',
        '/assets/arkset/natividad.png',
        '/assets/arkset/noe.png',
        '/assets/arkset/orando.png',
        '/assets/arkset/oso.png',
        '/assets/arkset/oveja.png',
        '/assets/arkset/oveja2.png',
        '/assets/arkset/paloma.png',
        '/assets/arkset/panda.png',
        '/assets/arkset/perro.png',
        '/assets/arkset/profeta.png',
        '/assets/arkset/serpiente.png',
        '/assets/arkset/tortuga.png',
        '/assets/arkset/tucan.png'
    ];

function addQuiet(cache, url) {
    return cache.add(url).catch(function () { /* tolerante: un asset que falta no rompe el install */ });
}

self.addEventListener('install', function (e) {
    e.waitUntil(
        caches.open(COREC)
            .then(function (c) { return Promise.all(PRECACHE_CORE.map(function (u) { return addQuiet(c, u); })); })
            .then(function () {
                return caches.open(ASSETS).then(function (c) {
                    return Promise.all(PRECACHE_EXTRA.map(function (u) { return addQuiet(c, u); }));
                });
            })
            .then(function () { return self.skipWaiting(); })
    );
});

self.addEventListener('activate', function (e) {
    e.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(keys.map(function (k) {
                // 'bf-books' (libros descargados) sobrevive los bumps de versión
                if (k.indexOf(VERSION) !== 0 && k !== 'bf-books') { return caches.delete(k); }
            }));
        }).then(function () { return self.clients.claim(); })
    );
});

function offlineHtml() {
    return new Response(
        '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">' +
        '<meta name="viewport" content="width=device-width,initial-scale=1">' +
        '<title>Sin conexión · Palabra Fiel</title><style>' +
        'body{font-family:system-ui;background:#f7f4ee;color:#232838;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;text-align:center}' +
        'div{max-width:22rem;padding:2rem}h1{font-size:1.4rem}p{color:#5c6372;line-height:1.6}' +
        '</style></head><body><div><h1>✝ Sin conexión</h1>' +
        '<p>Los capítulos que ya leíste y los juegos siguen disponibles sin internet. Vuelve atrás o reconéctate para continuar.</p></div></body></html>',
        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
}

self.addEventListener('fetch', function (e) {
    var req = e.request;
    if (req.method !== 'GET') { return; }
    var url = new URL(req.url);
    if (url.origin !== location.origin) { return; }
    var path = url.pathname;
    if (/^\/(api|juegos\/api)\//.test(path) || /\/(track|check|diag)\.php$/.test(path) || path === '/sw.js') { return; }

    // Navegaciones: network-first, guarda páginas visitadas para offline
    if (req.mode === 'navigate') {
        e.respondWith(
            fetch(req).then(function (res) {
                if (res.ok) {
                    var clone = res.clone();
                    caches.open(PAGES).then(function (c) {
                        c.put(req, clone);
                        c.keys().then(function (keys) {
                            if (keys.length > MAX_PAGES) { c.delete(keys[0]); }
                        });
                    });
                }
                return res;
            }).catch(function () {
                // cae al caché de visitadas y luego al precache del core
                return caches.match(req).then(function (hit) {
                    if (hit) { return hit; }
                    return caches.open(COREC).then(function (c) {
                        return c.match(new Request(path)).then(function (h2) {
                            return h2 || offlineHtml();
                        });
                    });
                });
            })
        );
        return;
    }

    // Assets e imágenes: stale-while-revalidate (incluye el precache)
    if (/^\/(assets|img)\//.test(path)) {
        e.respondWith(
            caches.match(req).then(function (hit) {
                var net = fetch(req).then(function (res) {
                    if (res.ok) { caches.open(ASSETS).then(function (c) { c.put(req, res.clone()); }); }
                    return res;
                }).catch(function () { return hit; });
                return hit || net;
            })
        );
        return;
    }
});
