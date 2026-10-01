// EPIC 08 / US-080 — registra el service worker tras cargar la página
// (archivo externo: la CSP script-src 'self' prohíbe scripts inline)
if ('serviceWorker' in navigator && document.body.dataset.sw) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register(document.body.dataset.sw);
    });
}
// deploy nudge
