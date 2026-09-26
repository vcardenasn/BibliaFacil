// Biblia Fácil — service worker (EPIC 08 / US-080).
// Páginas: network-first con caché de los capítulos ya visitados →
// lectura offline de lo que ya leíste. Assets (/assets/, /img/):
// stale-while-revalidate. Nunca cachea /api, /track.php ni POST.
var VERSION = 'bf-v1';
var PAGES = VERSION + '-pages';
var ASSETS = VERSION + '-assets';
var MAX_PAGES = 80;

self.addEventListener('install', function (e) { self.skipWaiting(); });

self.addEventListener('activate', function (e) {
    e.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(keys.map(function (k) {
                if (k.indexOf(VERSION) !== 0) { return caches.delete(k); }
            }));
        }).then(function () { return self.clients.claim(); })
    );
});

function offlineHtml() {
    return new Response(
        '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">' +
        '<meta name="viewport" content="width=device-width,initial-scale=1">' +
        '<title>Sin conexión · Biblia Fácil</title><style>' +
        'body{font-family:system-ui;background:#f7f4ee;color:#232838;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;text-align:center}' +
        'div{max-width:22rem;padding:2rem}h1{font-size:1.4rem}p{color:#5c6372;line-height:1.6}' +
        '</style></head><body><div><h1>✝ Sin conexión</h1>' +
        '<p>Los capítulos que ya leíste siguen disponibles sin internet. Vuelve atrás o reconéctate para continuar.</p></div></body></html>',
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
                return caches.match(req).then(function (hit) { return hit || offlineHtml(); });
            })
        );
        return;
    }

    // Assets e imágenes: stale-while-revalidate
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
