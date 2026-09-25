// Biblia Fácil — tema, tamaño de letra, switcher de versión,
// "continuar donde quedé", copiar/compartir, navegación por teclado.
(function () {
    'use strict';

    var root = document.documentElement;

    // ---- Tema oscuro ---------------------------------------------------------
    var theme = localStorage.getItem('bf_theme');
    if (!theme) {
        theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    root.setAttribute('data-theme', theme);
    var themeBtn = document.getElementById('themeBtn');
    if (themeBtn) {
        themeBtn.textContent = theme === 'dark' ? '☀' : '☾';
        themeBtn.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            localStorage.setItem('bf_theme', next);
            themeBtn.textContent = next === 'dark' ? '☀' : '☾';
        });
    }

    // ---- Tamaño de letra -----------------------------------------------------
    root.setAttribute('data-font', localStorage.getItem('bf_font') || '2');
    var fontBtn = document.getElementById('fontBtn');
    if (fontBtn) {
        fontBtn.addEventListener('click', function () {
            var next = (parseInt(root.getAttribute('data-font') || '2', 10) % 4) + 1;
            root.setAttribute('data-font', String(next));
            localStorage.setItem('bf_font', String(next));
        });
    }

    // ---- Switcher de versión: mismo pasaje, otra versión ---------------------
    var vswitch = document.getElementById('versionSwitch');
    if (vswitch) {
        vswitch.addEventListener('change', function () {
            var current = vswitch.getAttribute('data-version');
            var next = vswitch.value;
            var path = window.location.pathname.replace(/^\/+/, '');
            if (path.indexOf(current + '/') === 0) {
                path = next + path.slice(current.length);
            } else {
                path = next;
            }
            window.location.href = '/' + path + window.location.hash;
        });
    }

    // ---- Continuar donde quedé (cookie leída por el servidor en /) -----------
    var chapter = document.querySelector('.chapter[data-pos]');
    if (chapter) {
        document.cookie = 'bf_pos=' + encodeURIComponent(chapter.getAttribute('data-pos'))
            + ';path=/;max-age=31536000;SameSite=Lax';
    }

    // ---- Tap en versículo → muestra acciones (touch); click en botones -------
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('.va') : null;
        var verse = ev.target.closest ? ev.target.closest('.verse') : null;
        if (btn && verse) {
            var text = verse.getAttribute('data-text') || verse.textContent.trim();
            var ref = verse.getAttribute('data-ref') || '';
            var payload = '“' + text + '” — ' + ref;
            if (btn.getAttribute('data-act') === 'copy') {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(payload).then(function () { flash(btn, '✓ Copiado'); });
                }
            } else if (navigator.share) {
                navigator.share({ title: ref, text: payload }).catch(function () {});
            } else {
                window.open('https://wa.me/?text=' + encodeURIComponent(payload), '_blank', 'noopener');
            }
            return;
        }
        // Touch: tocar el versículo abre/cierra su barra de acciones
        if (verse) {
            verse.classList.toggle('open');
        }
    });

    function flash(btn, msg) {
        var old = btn.textContent;
        btn.textContent = msg;
        setTimeout(function () { btn.textContent = old; }, 1100);
    }

    // ---- Navegación con flechas ←/→ ------------------------------------------
    document.addEventListener('keydown', function (ev) {
        if (ev.target && /input|textarea|select/i.test(ev.target.tagName)) { return; }
        var link = null;
        if (ev.key === 'ArrowLeft') { link = document.querySelector('a[rel="prev"]'); }
        if (ev.key === 'ArrowRight') { link = document.querySelector('a[rel="next"]'); }
        if (link) { window.location.href = link.href; }
    });
})();
