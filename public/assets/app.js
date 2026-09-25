// Biblia Fácil — tema, tamaño de letra, "continuar donde quedé", copiar/compartir
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
        themeBtn.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            localStorage.setItem('bf_theme', next);
        });
    }

    // ---- Tamaño de letra -----------------------------------------------------
    var font = localStorage.getItem('bf_font') || '2';
    root.setAttribute('data-font', font);
    var fontBtn = document.getElementById('fontBtn');
    if (fontBtn) {
        fontBtn.addEventListener('click', function () {
            var next = (parseInt(root.getAttribute('data-font') || '2', 10) % 4) + 1;
            root.setAttribute('data-font', String(next));
            localStorage.setItem('bf_font', String(next));
        });
    }

    // ---- Continuar donde quedé (cookie leída por el servidor en /) -----------
    var chapter = document.querySelector('.chapter[data-pos]');
    if (chapter) {
        var pos = chapter.getAttribute('data-pos');
        document.cookie = 'bf_pos=' + encodeURIComponent(pos) + ';path=/;max-age=31536000;SameSite=Lax';
    }

    // ---- Acciones por versículo: copiar / compartir ---------------------------
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('.va') : null;
        if (!btn) { return; }
        var verse = btn.closest('.verse');
        if (!verse) { return; }
        var text = verse.getAttribute('data-text') || verse.textContent.trim();
        var ref = verse.getAttribute('data-ref') || '';
        var payload = '“' + text + '” — ' + ref;
        if (btn.getAttribute('data-act') === 'copy') {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(payload).then(function () { flash(btn, '✓'); });
            }
        } else if (navigator.share) {
            navigator.share({ title: ref, text: payload }).catch(function () {});
        } else {
            window.open('https://wa.me/?text=' + encodeURIComponent(payload), '_blank', 'noopener');
        }
    });

    function flash(btn, msg) {
        var old = btn.textContent;
        btn.textContent = msg;
        setTimeout(function () { btn.textContent = old; }, 900);
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
