// Biblia Fácil — preferencias, anotaciones (IndexedDB local-first),
// sheet de acciones por versículo, panel Apariencia, compartir/copiar.
// Sin cuenta: todo persiste en el dispositivo (localStorage + IndexedDB).
(function () {
    'use strict';

    var root = document.documentElement;

    // ============================ Preferencias ===============================
    var P = {
        get: function (k, d) { var v = localStorage.getItem('bf_' + k); return v === null ? d : v; },
        set: function (k, v) { localStorage.setItem('bf_' + k, v); }
    };

    function apply(k, v) { root.setAttribute('data-' + k, v); }

    // ============================ Métricas anónimas (EPIC 16) =================
    // Batch en memoria → sendBeacon a /track.php. Sin cookies, sin PII,
    // nunca envía texto del usuario. Respeta DoNotTrack/GlobalPrivacyControl.
    var TK = (function () {
        var off = navigator.doNotTrack === '1' || navigator.doNotTrack === 'yes' || navigator.globalPrivacyControl === true;
        var q = [];
        function t(m, d, n) {
            if (off) { return; }
            q.push([m, d || '', n || 1]);
            if (q.length >= 10) { flush(); }
        }
        function flush() {
            if (off || !q.length || !navigator.sendBeacon) { q = []; return; }
            try {
                navigator.sendBeacon('/track.php', new Blob([JSON.stringify({ e: q.splice(0) })], { type: 'application/json' }));
            } catch (e) { q = []; }
        }
        document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') { flush(); } });
        setInterval(flush, 20000);
        t.flushNow = flush;
        return t;
    })();
    window.BF_TRACK = TK; // lo usan también los juegos (game_win)

    var theme = P.get('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    apply('theme', theme);
    apply('font', P.get('font', '2'));
    apply('wj', P.get('wj', 'on'));       // palabras de Jesús en rojo
    apply('vnum', P.get('vnum', 'on'));   // números de versículo
    apply('flow', P.get('flow', 'verse'));// 'verse' | 'para' (párrafo fluido)
    apply('family', P.get('family', 'serif'));
    apply('accent', P.get('accent', 'indigo'));
    apply('lineh', P.get('lineh', 'normal'));
    apply('spacing', P.get('spacing', 'off')); // US-151: letra espaciada (dislexia)
    apply('zen', P.get('zen', 'off'));
    var zenBtn = null;
    syncZen();

    var themeBtn = document.getElementById('themeBtn');
    if (themeBtn) {
        syncThemeBtn();
        themeBtn.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            apply('theme', next); P.set('theme', next); syncThemeBtn(); syncPanel();
            TK('pref', 'theme:' + next);
        });
    }
    function syncThemeBtn() {
        var t = root.getAttribute('data-theme');
        themeBtn.textContent = t === 'dark' ? '☀' : (t === 'sepia' ? '◐' : '☾');
    }

    // ---- Panel Apariencia (⚙) ------------------------------------------------
    var prefBtn = document.getElementById('prefBtn');
    var panel = null;
    if (prefBtn) {
        prefBtn.addEventListener('click', function () {
            if (panel) { closePanel(); return; }
            panel = document.createElement('dialog');
            panel.className = 'prefpanel';
            panel.setAttribute('aria-label', 'Apariencia');
            panel.innerHTML =
                '<div class="pp-head"><strong>Apariencia</strong><button type="button" class="pp-close" aria-label="Cerrar apariencia">✕</button></div>' +
                '<div class="pp-row"><span>Tema</span><div class="seg" data-k="theme">' +
                seg('light', '☀', 'Claro') + seg('dark', '☾', 'Oscuro') + seg('sepia', '◐', 'Sepia') + seg('contrast', '◆', 'Alto contraste') + '</div></div>' +
                '<div class="pp-row"><span>Acento</span><div class="seg seg-acc" data-k="accent">' +
                seg('indigo', '●', 'Índigo') + seg('oliva', '●', 'Oliva') + seg('terracota', '●', 'Terracota') + seg('purpura', '●', 'Púrpura') + seg('teal', '●', 'Verde azulado') + '</div></div>' +
                '<div class="pp-row"><span>Tamaño</span><input type="range" min="1" max="4" step="1" data-k="font" value="' + P.get('font', '2') + '" aria-label="Tamaño de letra"></div>' +
                '<div class="pp-row"><span>Letra</span><div class="seg" data-k="family">' + seg('serif', 'Serif') + seg('sans', 'Sans') + '</div></div>' +
                '<div class="pp-row"><span>Interlineado</span><div class="seg" data-k="lineh">' + seg('compact', 'Compacto') + seg('normal', 'Normal') + seg('ample', 'Amplio') + '</div></div>' +
                '<div class="pp-row"><span>Palabras de Jesús en rojo</span><button type="button" class="tog" data-k="wj" role="switch" aria-label="Palabras de Jesús en rojo"></button></div>' +
                '<div class="pp-row"><span>Números de versículo</span><button type="button" class="tog" data-k="vnum" role="switch" aria-label="Números de versículo"></button></div>' +
                '<div class="pp-row"><span>Espaciado de letras</span><button type="button" class="tog" data-k="spacing" role="switch" aria-label="Espaciado amplio de letras"></button></div>' +
                '<div class="pp-row"><span>Párrafo fluido</span><button type="button" class="tog" data-k="flow" data-on="para" role="switch" aria-label="Párrafo fluido"></button></div>' +
                '<div class="pp-row"><span>Modo zen (sin barras)</span><button type="button" class="tog" data-k="zen" role="switch" aria-label="Modo zen (sin barras)"></button></div>';
            document.body.appendChild(panel);
            panel.showModal();
            prefBtn.setAttribute('aria-expanded', 'true');
            panel.querySelector('.pp-close').focus();
            syncPanel();
            panel.addEventListener('close', function () {
                panel.remove(); panel = null;
                prefBtn.setAttribute('aria-expanded', 'false');
                var restore = prefBtn.offsetParent !== null ? prefBtn : zenBtn;
                if (restore) { restore.focus(); }
            });
            panel.addEventListener('click', function (ev) {
                if (ev.target === panel) {
                    var box = panel.getBoundingClientRect();
                    if (ev.clientX < box.left || ev.clientX > box.right || ev.clientY < box.top || ev.clientY > box.bottom) { closePanel(); }
                    return;
                }
                if (ev.target.closest('.pp-close')) { closePanel(); return; }
                var b = ev.target.closest('[data-v], .tog');
                if (!b) { return; }
                var k = b.getAttribute('data-k') || b.parentElement.getAttribute('data-k');
                if (!k) { return; }
                if (b.classList.contains('tog')) {
                    var on = b.getAttribute('data-on') || 'on', off = on === 'on' ? 'off' : 'verse';
                    var cur = root.getAttribute('data-' + k);
                    var next = cur === on ? off : on;
                    apply(k, next); P.set(k, next);
                    TK('pref', k + ':' + next);
                } else {
                    apply(k, b.getAttribute('data-v')); P.set(k, b.getAttribute('data-v'));
                    TK('pref', k + ':' + b.getAttribute('data-v'));
                }
                syncPanel(); syncThemeBtn(); syncZen();
                if (k === 'zen' && root.getAttribute('data-zen') === 'on') { closePanel(); }
            });
        });
    }
    function seg(v, label, accessibleName) { return '<button type="button" data-v="' + v + '"' + (accessibleName ? ' aria-label="' + accessibleName + '"' : '') + '>' + label + '</button>'; }
    function syncPanel() {
        if (!panel) { return; }
        panel.querySelectorAll('.seg [data-v]').forEach(function (b) {
            var selected = root.getAttribute('data-' + b.parentElement.getAttribute('data-k')) === b.getAttribute('data-v');
            b.classList.toggle('on', selected);
            b.setAttribute('aria-pressed', String(selected));
        });
        panel.querySelectorAll('.tog').forEach(function (b) {
            var on = b.getAttribute('data-on') || 'on';
            var isOn = root.getAttribute('data-' + b.getAttribute('data-k')) === on;
            b.classList.toggle('on', isOn);
            b.setAttribute('aria-checked', String(isOn));
        });
        var r = panel.querySelector('input[type=range]');
        if (r) { r.value = P.get('font', '2'); }
        if (r && !r._bound) {
            r._bound = true;
            r.addEventListener('input', function () { apply('font', r.value); P.set('font', r.value); });
            r.addEventListener('change', function () { TK('pref', 'font:' + r.value); });
        }
    }
    function closePanel() {
        if (panel && panel.open) { panel.close(); }
    }

    // ---- Modo zen: oculta chrome, sale con ✕ flotante -------------------------
    function syncZen() {
        var on = root.getAttribute('data-zen') === 'on';
        if (on && !zenBtn) {
            zenBtn = document.createElement('button');
            zenBtn.type = 'button';
            zenBtn.className = 'zen-exit';
            zenBtn.textContent = '✕';
            zenBtn.title = 'Salir del modo zen';
            zenBtn.setAttribute('aria-label', 'Salir del modo zen');
            zenBtn.addEventListener('click', function () {
                apply('zen', 'off'); P.set('zen', 'off'); syncZen(); syncPanel();
                TK('pref', 'zen:off');
            });
            document.body.appendChild(zenBtn);
        } else if (!on && zenBtn) {
            zenBtn.remove(); zenBtn = null;
        }
    }

    // ---- Switcher de versión --------------------------------------------------
    var vswitch = document.getElementById('versionSwitch');
    if (vswitch) {
        vswitch.addEventListener('change', function () {
            var cur = vswitch.getAttribute('data-version'), next = vswitch.value;
            TK('vswitch', next);
            var path = window.location.pathname.replace(/^\/+/, '');
            path = path.indexOf(cur + '/') === 0 ? next + path.slice(cur.length) : next;
            window.location.href = '/' + path + window.location.hash;
        });
    }

    // ---- Continuar donde quedé + historial/racha + scroll-restore -------------
    var bookFilter = document.getElementById('book-filter');
    if (bookFilter) {
        var bookSections = document.querySelectorAll('.book-section');
        var bookStatus = document.getElementById('book-filter-status');
        document.querySelectorAll('.book-tools nav a').forEach(function (link) {
            link.addEventListener('click', function () {
                bookFilter.value = '';
                bookFilter.dispatchEvent(new Event('input'));
            });
        });
        bookFilter.addEventListener('input', function () {
            var query = bookFilter.value.trim().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
            var count = 0;
            bookSections.forEach(function (section) {
                var visible = 0;
                section.querySelectorAll('.book-grid li').forEach(function (item) {
                    var name = item.textContent.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
                    item.hidden = !name.includes(query);
                    if (!item.hidden) { visible++; }
                });
                section.hidden = visible === 0;
                count += visible;
            });
            bookStatus.hidden = !query;
            bookStatus.textContent = count ? count + ' libro(s) encontrado(s).' : 'No se encontraron libros. Prueba otro nombre.';
        });
    }
    var chapterEl = document.querySelector('.chapter[data-pos]');
    if (chapterEl) {
        var pos = chapterEl.getAttribute('data-pos');
        document.cookie = 'bf_pos=' + encodeURIComponent(pos)
            + ';path=/;max-age=31536000;SameSite=Lax';

        // Racha: día leído en localStorage (YYYY-MM-DD)
        var days = [];
        try { days = JSON.parse(P.get('days', '[]')); } catch (e) {}
        var today = new Date().toISOString().slice(0, 10);
        if (days.indexOf(today) < 0) {
            days.push(today);
            P.set('days', JSON.stringify(days.slice(-500)));
        }

        // Historial de lectura (US-130): últimas posiciones para /mias
        var hist = [];
        try { hist = JSON.parse(P.get('hist', '[]')); } catch (e) {}
        hist = hist.filter(function (h) { return h && h.p !== pos; });
        hist.unshift({ p: pos, l: chapterEl.getAttribute('data-label') || pos, t: Date.now() });
        P.set('hist', JSON.stringify(hist.slice(0, 30)));

        // Scroll-restore por capítulo
        var posKey = 'bf_scr_' + pos;
        if (!window.location.hash) {
            var sv = localStorage.getItem(posKey);
            if (sv && sv !== '1') {
                var t = document.getElementById('v' + sv);
                if (t) { setTimeout(function () { t.scrollIntoView(); }, 60); }
            }
        }
        var scrT = null;
        window.addEventListener('scroll', function () {
            clearTimeout(scrT);
            scrT = setTimeout(function () {
                var vs = document.querySelectorAll('.chapter .verse');
                for (var i = 0; i < vs.length; i++) {
                    if (vs[i].getBoundingClientRect().top > 70) {
                        localStorage.setItem(posKey, vs[i].id.slice(1));
                        return;
                    }
                }
            }, 220);
        }, { passive: true });
    }

    // ============================ Anotaciones (IndexedDB) ======================
    var DB = {
        _p: null,
        open: function () {
            if (!window.indexedDB) { return Promise.resolve(null); }
            if (!this._p) {
                this._p = new Promise(function (res) {
                    var rq = indexedDB.open('bibliafacil', 1);
                    rq.onupgradeneeded = function (e) {
                        e.target.result.createObjectStore('ann', { keyPath: 'id' });
                    };
                    rq.onsuccess = function (e) { res(e.target.result); };
                    rq.onerror = function () { res(null); };
                });
            }
            return this._p;
        },
        store: function (mode) {
            return this.open().then(function (d) {
                return d ? d.transaction('ann', mode || 'readonly').objectStore('ann') : null;
            });
        },
        put: function (rec) { return this.store('readwrite').then(function (s) { if (s) { s.put(rec); } }); },
        del: function (id) { return this.store('readwrite').then(function (s) { if (s) { s.delete(id); } }); },
        all: function () {
            return this.store().then(function (s) {
                return s ? new Promise(function (r) {
                    var q = s.getAll();
                    q.onsuccess = function () { r(q.result || []); };
                    q.onerror = function () { r([]); };
                }) : [];
            });
        }
    };

    // Clave: version|slug|cap|ver
    function keyOf(verseEl) {
        var pos = (chapterEl ? chapterEl.getAttribute('data-pos') : '') || '';
        return pos.replace(/\//g, '|') + '|' + verseEl.id.replace(/^v/, '');
    }

    var annMap = {}; // id → record
    function applyMarks() {
        if (!chapterEl) { return; }
        DB.all().then(function (list) {
            annMap = {};
            list.forEach(function (r) { annMap[r.id] = r; });
            document.querySelectorAll('.verse').forEach(function (el) {
                paintVerse(el, annMap[keyOf(el)]);
            });
        });
    }
    function paintVerse(el, rec) {
        for (var i = 1; i <= 5; i++) { el.classList.remove('hl' + i); }
        el.classList.remove('has-note', 'has-fav');
        var marks = el.querySelector('.vmarks');
        if (marks) { marks.remove(); }
        if (!rec) { return; }
        if (rec.color) { el.classList.add('hl' + rec.color); }
        var html = '';
        if (rec.note) { el.classList.add('has-note'); html += '<i title="Tiene nota">✎</i>'; }
        if (rec.fav) { el.classList.add('has-fav'); html += '<i title="Favorito">♥</i>'; }
        if (html) {
            var s = document.createElement('span');
            s.className = 'vmarks';
            s.innerHTML = html;
            el.appendChild(s);
        }
    }

    // ============================ Sheet de versículo ===========================
    var sheet = null, sheetVerse = null, sheetReturnFocus = null;
    function openSheet(el) {
        TK('sheet');
        closeSheet();
        sheetVerse = el;
        var ae = document.activeElement;
        sheetReturnFocus = (ae && ae !== document.body && ae !== document.documentElement) ? ae : el;
        el.classList.add('open');
        var id = keyOf(el);
        var rec = annMap[id] || {};
        var ref = el.getAttribute('data-ref') || '';
        var text = el.getAttribute('data-text') || el.textContent.trim();
        var kp = id.split('|'); // version|slug|cap|ver → URL corta /v/slug/cap/ver (US-201)
        var surl = kp.length === 4 ? location.origin + '/v/' + kp[1] + '/' + kp[2] + '/' + kp[3] + '?v=' + kp[0] : location.href;
        var cmpUrl = chapterEl ? chapterEl.getAttribute('data-cmp') : null;

        sheet = document.createElement('div');
        sheet._vtext = text; sheet._vref = ref;
        sheet.className = 'vsheet';
        sheet.setAttribute('role', 'dialog');
        sheet.setAttribute('aria-modal', 'true');
        sheet.setAttribute('aria-label', 'Opciones del versículo ' + ref);
        sheet.innerHTML =
            '<div class="vs-backdrop"></div><div class="vs-card">' +
            '<div class="vs-head"><strong>' + esc(ref) + '</strong>' +
            '<button type="button" class="vs-x" aria-label="Cerrar">✕</button></div>' +
            '<div class="vs-row vs-colors"><span class="vs-lab">Resaltar</span>' +
            [1, 2, 3, 4, 5].map(function (c) {
                return '<button type="button" class="sw sw' + c + (rec.color === c ? ' on' : '') + '" data-c="' + c + '" aria-label="Color ' + c + '"></button>';
            }).join('') +
            '<button type="button" class="sw sw0" data-c="0" title="Quitar resaltado">✕</button></div>' +
            '<div class="vs-row vs-acts">' +
            '<button type="button" data-a="note" class="va2' + (rec.note ? ' on' : '') + '">✎ Nota</button>' +
            '<button type="button" data-a="fav" class="va2' + (rec.fav ? ' on' : '') + '">♥ Favorito</button>' +
            '<button type="button" data-a="copy" class="va2">⧉ Copiar</button>' +
            '<button type="button" data-a="share" class="va2">↗ Compartir</button>' +
            '<button type="button" data-a="img" class="va2">🖼 Imagen</button>' +
            '<button type="button" data-a="range" class="va2" aria-expanded="false">⇅ Rango</button>' +
            (cmpUrl ? '<a class="va2" href="' + esc(cmpUrl) + '">⇄ Comparar</a>' : '') +
            '</div>' +
            '<div class="vs-range" hidden><label>Hasta v. <select class="vs-range-sel"></select></label>' +
            '<button type="button" data-a="copyrange" class="va2">⧉ Copiar rango</button></div>' +
            '<div class="vs-note" hidden><textarea rows="3" maxlength="2000" placeholder="Escribe tu nota…">' + esc(rec.note || '') + '</textarea>' +
            '<div class="vs-note-btns"><button type="button" data-a="save" class="va2 on">Guardar</button>' +
            (rec.note ? '<button type="button" data-a="delnote" class="va2">Borrar nota</button>' : '') + '</div></div>' +
            '</div>';
        document.body.appendChild(sheet);
        requestAnimationFrame(function () { sheet.classList.add('show'); });

        sheet.querySelector('.vs-backdrop').addEventListener('click', closeSheet);
        sheet.querySelector('.vs-x').addEventListener('click', closeSheet);

        // Foco dentro del diálogo + trampa de Tab (UX-04)
        var closeBtn = sheet.querySelector('.vs-x');
        if (closeBtn) { closeBtn.focus(); }
        sheet.addEventListener('keydown', function (ev) {
            if (ev.key !== 'Tab') { return; }
            var items = sheet.querySelectorAll('button, textarea, [href], input, select, [tabindex]:not([tabindex="-1"])');
            var vis = [].filter.call(items, function (n) { return !n.disabled && n.offsetParent !== null; });
            if (!vis.length) { return; }
            var first = vis[0], last = vis[vis.length - 1];
            if (ev.shiftKey && document.activeElement === first) { ev.preventDefault(); last.focus(); }
            else if (!ev.shiftKey && document.activeElement === last) { ev.preventDefault(); first.focus(); }
        });

        sheet.addEventListener('click', function (ev) {
            var sw = ev.target.closest('.sw');
            var act = ev.target.closest('[data-a]');
            if (sw) {
                var c = parseInt(sw.getAttribute('data-c'), 10);
                rec.color = c || null;
                if (c) { TK('ann', 'hl'); }
                saveAnn(id, rec, ref);
                paintVerse(sheetVerse, rec.color || rec.note || rec.fav ? rec : null);
                sheet.querySelectorAll('.sw').forEach(function (b) { b.classList.toggle('on', parseInt(b.getAttribute('data-c'), 10) === c); });
                return;
            }
            if (!act) { return; }
            var a = act.getAttribute('data-a');
            if (a === 'note') {
                var nz = sheet.querySelector('.vs-note');
                nz.hidden = !nz.hidden;
                if (!nz.hidden) { nz.querySelector('textarea').focus(); }
            } else if (a === 'save') {
                rec.note = sheet.querySelector('textarea').value.trim() || null;
                if (rec.note) { TK('ann', 'note'); }
                saveAnn(id, rec, ref);
                act.textContent = '✓ Guardada';
                setTimeout(closeSheet, 700);
            } else if (a === 'delnote') {
                rec.note = null;
                saveAnn(id, rec, ref);
                paintVerse(sheetVerse, rec.color || rec.fav ? rec : null);
                closeSheet();
            } else if (a === 'fav') {
                rec.fav = !rec.fav;
                if (rec.fav) { TK('ann', 'fav'); }
                saveAnn(id, rec, ref);
                act.classList.toggle('on', !!rec.fav);
                paintVerse(sheetVerse, rec.color || rec.note || rec.fav ? rec : null);
            } else if (a === 'copy') {
                var payload = '“' + text + '” — ' + ref + '\n' + surl;
                TK('share', 'copy');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(payload).then(function () {
                        act.textContent = '✓ Copiado';
                        setTimeout(function () { act.textContent = '⧉ Copiar'; }, 1100);
                    });
                }
            } else if (a === 'range') {
                // US-143 — copiar un rango de versículos a partir de este
                var rz = sheet.querySelector('.vs-range');
                var sel = rz.querySelector('select');
                if (!sel.options.length) {
                    var from = parseInt(sheetVerse.id.slice(1), 10);
                    document.querySelectorAll('.chapter .verse').forEach(function (v) {
                        var n = parseInt(v.id.slice(1), 10);
                        if (n > from) {
                            var o = document.createElement('option');
                            o.value = n; o.textContent = n;
                            sel.appendChild(o);
                        }
                    });
                }
                rz.hidden = !rz.hidden;
                act.setAttribute('aria-expanded', String(!rz.hidden));
            } else if (a === 'copyrange') {
                var rsel = sheet.querySelector('.vs-range-sel');
                var vFrom = parseInt(sheetVerse.id.slice(1), 10);
                var vTo = rsel.value ? parseInt(rsel.value, 10) : 0;
                var parts = [];
                document.querySelectorAll('.chapter .verse').forEach(function (v) {
                    var n = parseInt(v.id.slice(1), 10);
                    if (n >= vFrom && n <= vTo) {
                        parts.push(v.getAttribute('data-text') || v.textContent.trim());
                    }
                });
                var base = ref.replace(/:\d+.*$/, '');
                var rangeRef = vTo > vFrom ? base + ':' + vFrom + '-' + vTo : ref;
                var rUrl = location.origin + '/' + chapterEl.getAttribute('data-pos') + '#v' + vFrom;
                TK('share', 'range');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText('“' + parts.join(' ') + '” — ' + rangeRef + '\n' + rUrl).then(function () {
                        act.textContent = '✓ Copiado';
                        setTimeout(function () { act.textContent = '⧉ Copiar rango'; }, 1100);
                    });
                }
            } else if (a === 'share') {
                var pl = '“' + text + '” — ' + ref + ' ' + surl;
                TK('share', navigator.share ? 'native' : 'wa');
                if (navigator.share) { navigator.share({ title: ref, text: pl }).catch(function () {}); }
                else { window.open('https://wa.me/?text=' + encodeURIComponent(pl), '_blank', 'noopener'); }
            } else if (a === 'img') {
                TK('img', 'story');
                imgMode(sheet, text, ref, 'story');
            } else if (a === 'fmt') {
                TK('img', act.getAttribute('data-fmt'));
                imgMode(sheet, sheet._vtext, sheet._vref, act.getAttribute('data-fmt'));
            } else if (a === 'back') {
                var el2 = sheetVerse; closeSheet(); openSheet(el2);
            } else if (a === 'dl') {
                TK('share', 'imgdl');
                sheet._cv.toBlob(function (b) { downloadBlob(b, ref); });
            } else if (a === 'shimg') {
                TK('share', 'img');
                sheet._cv.toBlob(function (b) {
                    var f = new File([b], 'versiculo.png', { type: 'image/png' });
                    if (navigator.canShare && navigator.canShare({ files: [f] })) {
                        navigator.share({ files: [f], title: ref }).catch(function () {});
                    } else { downloadBlob(b, ref); }
                });
            }
        });
    }
    function saveAnn(id, rec, ref) {
        rec.id = id;
        var parts = id.split('|');
        rec.version = parts[0]; rec.slug = parts[1];
        rec.chapter = parseInt(parts[2], 10); rec.verse = parseInt(parts[3], 10);
        rec.ref = ref;
        rec.ts = Date.now();
        if (!rec.color && !rec.note && !rec.fav) {
            delete annMap[id];
            DB.del(id);
        } else {
            annMap[id] = rec;
            DB.put(rec);
        }
    }
    function closeSheet() {
        if (sheet) { sheet.remove(); sheet = null; }
        if (sheetVerse) { sheetVerse.classList.remove('open'); sheetVerse = null; }
        if (sheetReturnFocus && sheetReturnFocus.focus) {
            sheetReturnFocus.focus();
        }
        sheetReturnFocus = null;
    }
    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // ============================ Imagen de versículo ==========================
    var IMG_FMTS = { story: [1080, 1920], square: [1080, 1080], wide: [1600, 840] };

    function imgMode(sh, text, ref, fmt) {
        sh._vtext = text; sh._vref = ref;
        var card = sh.querySelector('.vs-card');
        card.innerHTML =
            '<div class="vs-head"><button type="button" class="vs-x" data-a="back">← Volver</button>' +
            '<strong>' + esc(ref) + '</strong><span></span></div>' +
            '<div class="vs-row"><div class="seg">' +
            '<button type="button" data-a="fmt" data-fmt="story"' + (fmt === 'story' ? ' class="on"' : '') + '>Historia</button>' +
            '<button type="button" data-a="fmt" data-fmt="square"' + (fmt === 'square' ? ' class="on"' : '') + '>Cuadrada</button>' +
            '<button type="button" data-a="fmt" data-fmt="wide"' + (fmt === 'wide' ? ' class="on"' : '') + '>Ancha</button></div></div>' +
            '<div class="vs-imgwrap"><img alt="Vista previa de la imagen"></div>' +
            '<div class="vs-row vs-acts">' +
            '<button type="button" data-a="dl" class="va2 on">⬇ Descargar PNG</button>' +
            '<button type="button" data-a="shimg" class="va2">↗ Compartir</button></div>';
        sh._cv = drawVerseImage(text, ref, fmt);
        card.querySelector('.vs-imgwrap img').src = sh._cv.toDataURL('image/png');
    }

    function drawVerseImage(text, ref, fmt) {
        var w = IMG_FMTS[fmt][0], h = IMG_FMTS[fmt][1];
        var cv = document.createElement('canvas');
        cv.width = w; cv.height = h;
        var x = cv.getContext('2d');

        var g = x.createLinearGradient(0, 0, w * .3, h);
        g.addColorStop(0, '#2e4a8a'); g.addColorStop(1, '#16233f');
        x.fillStyle = g; x.fillRect(0, 0, w, h);

        var glow = x.createRadialGradient(w / 2, h * .3, 0, w / 2, h * .3, w * .85);
        glow.addColorStop(0, 'rgba(255,235,180,.16)'); glow.addColorStop(1, 'rgba(255,235,180,0)');
        x.fillStyle = glow; x.fillRect(0, 0, w, h);

        x.fillStyle = '#b8912f';
        x.fillRect(w / 2 - 70, h * .14, 140, 7);

        var maxW = w * .8, fs = Math.round(w * .075), lh = 1.38, lines;
        x.textAlign = 'center';
        x.fillStyle = '#fdf7ea';
        do {
            x.font = 'italic 600 ' + fs + 'px Georgia, "Times New Roman", serif';
            lines = wrapLines(x, '“' + text + '”', maxW);
            if (lines.length * fs * lh < h * .5 || fs <= 26) { break; }
            fs -= 4;
        } while (fs > 26);
        var y0 = h * .52 - (lines.length * fs * lh) / 2;
        lines.forEach(function (l, i) { x.fillText(l, w / 2, y0 + i * fs * lh); });

        x.font = '700 ' + Math.round(w * .032) + 'px Georgia, serif';
        x.fillStyle = '#d9b95c';
        x.fillText(ref.toUpperCase(), w / 2, y0 + lines.length * fs * lh + w * .055);

        x.font = '600 ' + Math.round(w * .024) + 'px Georgia, serif';
        x.fillStyle = 'rgba(253,247,234,.7)';
        x.fillText('✝ ' + (location.host || 'Biblia Fácil'), w / 2, h * .94); // US-203: dominio transparente
        return cv;
    }

    function wrapLines(x, text, maxW) {
        var words = text.split(/\s+/), lines = [], line = '';
        words.forEach(function (wd) {
            var t = line ? line + ' ' + wd : wd;
            if (line && x.measureText(t).width > maxW) { lines.push(line); line = wd; }
            else { line = t; }
        });
        if (line) { lines.push(line); }
        return lines;
    }

    function downloadBlob(blob, ref) {
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = ref.replace(/[^\wáéíóúñ]+/gi, '-').toLowerCase() + '.png';
        a.click();
        URL.revokeObjectURL(a.href);
    }

    // Tap/click en versículo → sheet (no si el click fue en un botón/enlace)
    document.addEventListener('click', function (ev) {
        var verse = ev.target.closest ? ev.target.closest('.verse') : null;
        if (!verse || ev.target.closest('a,button,select,input,textarea')) { return; }
        if (sheetVerse === verse) { closeSheet(); } else { openSheet(verse); }
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') { closeSheet(); closePanel(); }
    });

    applyMarks();

    // ============================ Mis anotaciones ==============================
    var mias = document.getElementById('miasApp');
    if (mias) {
        var filter = 'all', q = '', book = '';
        var bookSel = document.getElementById('miasBook');
        var countEl = document.getElementById('miasCount');

        function bookName(r) {
            return ((r.ref || '').replace(/\s+\d+:\d+.*$/, '').trim()) || r.slug || '';
        }

        // Racha de lectura
        var sb = document.getElementById('streakBox');
        if (sb) {
            var dlist = [];
            try { dlist = JSON.parse(P.get('days', '[]')); } catch (e) {}
            if (dlist.length) {
                var st = 0, t = new Date();
                if (dlist.indexOf(t.toISOString().slice(0, 10)) < 0) { t.setDate(t.getDate() - 1); }
                while (dlist.indexOf(t.toISOString().slice(0, 10)) >= 0) {
                    st++; t.setDate(t.getDate() - 1);
                }
                sb.hidden = false;
                sb.innerHTML = '<strong>🔥 ' + st + (st === 1 ? ' día' : ' días') + ' seguidos</strong>' +
                    '<span>' + dlist.length + (dlist.length === 1 ? ' día' : ' días') + ' de lectura en total</span>';
            }
        }

        // Historial de lectura (US-130): últimos capítulos visitados, en este dispositivo
        var histBox = document.getElementById('histBox');
        if (histBox) {
            var hlist = [];
            try { hlist = JSON.parse(P.get('hist', '[]')); } catch (e) {}
            if (hlist.length) {
                var DAY = 86400000;
                var rel = function (ts) {
                    var d = Math.floor((Date.now() - ts) / DAY);
                    return d <= 0 ? 'hoy' : (d === 1 ? 'ayer' : 'hace ' + d + ' días');
                };
                histBox.hidden = false;
                document.getElementById('histList').innerHTML = hlist.slice(0, 12).map(function (h) {
                    return '<li><a href="/' + esc(h.p) + '">' + esc(h.l) + '</a>' +
                        ' <span class="muted">' + rel(h.t) + '</span></li>';
                }).join('');
            }
        }

        function renderMias() {
            DB.all().then(function (all) {
                if (bookSel) {
                    var names = {};
                    all.forEach(function (r) { if (r.slug) { names[r.slug] = bookName(r); } });
                    var slugs = Object.keys(names).sort(function (a, b) { return names[a].localeCompare(names[b], 'es'); });
                    if (book && !(book in names)) { book = ''; }
                    bookSel.innerHTML = '<option value="">Todos los libros</option>' +
                        slugs.map(function (s) {
                            return '<option value="' + esc(s) + '"' + (s === book ? ' selected' : '') + '>' + esc(names[s]) + '</option>';
                        }).join('');
                    bookSel.hidden = !slugs.length;
                }
                var filtered = all.filter(function (r) {
                    if (book && r.slug !== book) { return false; }
                    if (filter === 'hl' && !r.color) { return false; }
                    if (filter === 'note' && !r.note) { return false; }
                    if (filter === 'fav' && !r.fav) { return false; }
                    if (q) {
                        var hay = ((r.ref || '') + ' ' + (r.note || '')).toLowerCase();
                        if (hay.indexOf(q) < 0) { return false; }
                    }
                    return true;
                }).sort(function (a, b) { return (b.ts || 0) - (a.ts || 0); });

                if (countEl) {
                    countEl.textContent = !all.length ? ''
                        : (!filtered.length ? 'Sin anotaciones con estos filtros.'
                        : filtered.length + (filtered.length === 1 ? ' anotación' : ' anotaciones') +
                          (filter !== 'all' || q || book ? ' con los filtros actuales.' : ' en total.'));
                }

                var box = document.getElementById('miasList');
                if (!filtered.length) {
                    box.innerHTML = all.length
                        ? '<p class="muted" style="padding:2rem 0;text-align:center">Nada coincide con los filtros actuales.<br><button type="button" data-clear>Limpiar filtros</button></p>'
                        : '<p class="muted" style="padding:2rem 0;text-align:center">Aún no tienes anotaciones.<br>Toca un versículo en el lector para resaltarlo, anotarlo o marcarlo ♥.</p>';
                    return;
                }
                var list = filtered;
                box.innerHTML = list.map(function (r) {
                    var url = '/' + r.version + '/' + r.slug + '/' + r.chapter + '#v' + r.verse;
                    var tags = '';
                    if (r.color) { tags += '<span class="tag hl' + r.color + '"></span>'; }
                    if (r.note) { tags += '<span class="tag">✎</span>'; }
                    if (r.fav) { tags += '<span class="tag">♥</span>'; }
                    return '<div class="mias-item" data-id="' + esc(r.id) + '">' +
                        '<div class="mi-head"><a href="' + url + '"><strong>' + esc(r.ref || r.id) + '</strong></a>' +
                        '<span class="mi-ver">' + esc((r.version || '').toUpperCase()) + '</span>' + tags + '</div>' +
                        (r.note ? '<p class="mi-note">' + esc(r.note) + '</p>' : '') +
                        '<button type="button" class="mi-del" title="Borrar">Borrar</button></div>';
                }).join('');
            });
        }
        function setChip(chip) {
            filter = chip.getAttribute('data-f');
            mias.querySelectorAll('[data-f]').forEach(function (c) {
                var on = c === chip;
                c.classList.toggle('on', on);
                c.setAttribute('aria-checked', String(on));
                c.tabIndex = on ? 0 : -1;
            });
            renderMias();
        }
        function clearFilters() {
            filter = 'all'; q = ''; book = '';
            var chips = mias.querySelectorAll('[data-f]');
            chips.forEach(function (c) {
                var on = c.getAttribute('data-f') === 'all';
                c.classList.toggle('on', on);
                c.setAttribute('aria-checked', String(on));
                c.tabIndex = on ? 0 : -1;
            });
            var qInput2 = document.getElementById('miasQ');
            if (qInput2) { qInput2.value = ''; }
            if (bookSel) { bookSel.value = ''; }
            renderMias();
        }
        mias.addEventListener('keydown', function (ev) {
            var chip = ev.target.closest ? ev.target.closest('[data-f]') : null;
            if (!chip) { return; }
            var chips = [].slice.call(mias.querySelectorAll('[data-f]'));
            var i = chips.indexOf(chip);
            var next = -1;
            if (ev.key === 'ArrowRight') { next = (i + 1) % chips.length; }
            else if (ev.key === 'ArrowLeft') { next = (i - 1 + chips.length) % chips.length; }
            else if (ev.key === 'Home') { next = 0; }
            else if (ev.key === 'End') { next = chips.length - 1; }
            if (next >= 0) { ev.preventDefault(); chips[next].focus(); setChip(chips[next]); }
        });
        mias.addEventListener('click', function (ev) {
            var chip = ev.target.closest('[data-f]');
            var del = ev.target.closest('.mi-del');
            if (ev.target.closest('[data-clear]')) { clearFilters(); return; }
            if (chip) {
                setChip(chip);
            } else if (del) {
                var item = del.closest('.mias-item');
                DB.del(item.getAttribute('data-id')).then(function () {
                    item.remove();
                    renderMias();
                });
            }
        });
        // Export / import JSON
        var expBtn = document.getElementById('miasExport');
        if (expBtn) {
            expBtn.addEventListener('click', function () {
                TK('ann', 'export');
                DB.all().then(function (list) {
                    var blob = new Blob([JSON.stringify({ app: 'bibliafacil', v: 1, ann: list }, null, 2)], { type: 'application/json' });
                    var a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = 'biblia-facil-anotaciones.json';
                    a.click();
                    URL.revokeObjectURL(a.href);
                });
            });
        }
        var qInput = document.getElementById('miasQ');
        if (qInput) {
            qInput.addEventListener('input', function () {
                q = qInput.value.trim().toLowerCase();
                renderMias();
            });
        }
        if (bookSel) {
            bookSel.addEventListener('change', function () {
                book = bookSel.value;
                renderMias();
            });
        }
        var impInput = document.getElementById('miasImport');
        var impBtn = document.getElementById('miasImportBtn');
        if (impBtn && impInput) {
            impBtn.addEventListener('click', function () { impInput.click(); });
        }
        if (impInput) {
            impInput.addEventListener('change', function () {
                var f = impInput.files[0];
                if (!f) { return; }
                var rd = new FileReader();
                rd.onload = function () {
                    try {
                        var data = JSON.parse(rd.result);
                        var list = Array.isArray(data) ? data : (data.ann || []);
                        var chain = Promise.resolve();
                        list.forEach(function (r) {
                            if (r && r.id) { chain = chain.then(function () { return DB.put(r); }); }
                        });
                        chain.then(renderMias);
                        TK('ann', 'import');
                    } catch (e) { alert('No pude leer ese archivo JSON.'); }
                };
                rd.readAsText(f);
                impInput.value = '';
            });
        }
        renderMias();
    }

    // ============================ Contexto en búsqueda (US-142) ===============
    // Botón "± contexto" por resultado → /api/contexto devuelve ±3 versículos.
    document.addEventListener('click', function (ev) {
        var cb = ev.target.closest ? ev.target.closest('.ctx-btn') : null;
        if (!cb) { return; }
        var li = cb.closest('.result');
        var box = li.querySelector('.ctx');
        if (!box) {
            box = document.createElement('div');
            box.className = 'ctx';
            box.id = 'ctx-' + cb.getAttribute('data-b') + '-' + cb.getAttribute('data-c') + '-' + cb.getAttribute('data-n');
            box.hidden = true;
            cb.setAttribute('aria-controls', box.id);
            li.appendChild(box);
        }
        if (!box.hidden) { box.hidden = true; cb.setAttribute('aria-expanded', 'false'); return; }
        if (box.dataset.loaded) { box.hidden = false; cb.setAttribute('aria-expanded', 'true'); return; }
        cb.disabled = true;
        var hit = parseInt(cb.getAttribute('data-n'), 10);
        fetch('/api/contexto?v=' + encodeURIComponent(cb.getAttribute('data-v')) +
            '&b=' + encodeURIComponent(cb.getAttribute('data-b')) +
            '&c=' + encodeURIComponent(cb.getAttribute('data-c')) +
            '&n=' + encodeURIComponent(cb.getAttribute('data-n')))
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (!j.ok || !j.verses.length) { throw new Error('sin datos'); }
                j.verses.forEach(function (v) {
                    var p = document.createElement('p');
                    p.className = 'ctx-v' + (v.v === hit ? ' ctx-hit' : '');
                    var sup = document.createElement('sup');
                    sup.textContent = v.v;
                    p.appendChild(sup);
                    p.appendChild(document.createTextNode(v.t));
                    box.appendChild(p);
                });
                box.dataset.loaded = '1';
                box.hidden = false;
                cb.setAttribute('aria-expanded', 'true');
            })
            .catch(function () { box.textContent = 'No se pudo cargar el contexto.'; box.hidden = false; })
            .finally(function () { cb.disabled = false; });
    });

    // ============================ Planes de lectura (EPIC 05) =================
    // Estado local por plan: bf_plan_{slug} = {s: inicio(ms), d: [días hechos]}
    var planApp = document.getElementById('planApp');
    if (planApp) {
        var pslug = planApp.getAttribute('data-slug');
        var pkey = 'plan_' + pslug;
        var pst = null;
        try { pst = JSON.parse(P.get(pkey, 'null')); } catch (e) {}
        if (!pst || typeof pst !== 'object') { pst = { s: 0, d: [] }; }
        if (!Array.isArray(pst.d)) { pst.d = []; }
        var dayEls = planApp.querySelectorAll('.plan-day');
        var totalDays = dayEls.length;
        var bar = document.getElementById('planBar');
        var lab = document.getElementById('planLabel');
        var pace = document.getElementById('planPace');
        var barWrap = planApp.querySelector('.plan-bar');
        var btnStart = document.getElementById('planStart');
        var btnReset = document.getElementById('planReset');
        var goBtn = document.getElementById('planGo');

        function nextPlanDay() {
            for (var i = 1; i <= totalDays; i++) { if (pst.d.indexOf(i) < 0) { return i; } }
            return 0;
        }
        function paintPlan() {
            var done = pst.d.length;
            lab.textContent = done + ' de ' + totalDays + ' días';
            bar.style.width = (done * 100 / totalDays).toFixed(1) + '%';
            if (barWrap) { barWrap.setAttribute('aria-valuenow', String(done)); }
            var nx = nextPlanDay();
            if (goBtn) {
                goBtn.setAttribute('href', '#d' + (nx || totalDays));
                goBtn.textContent = nx ? 'Ir al día ' + nx + ' ↓' : 'Plan completado';
            }
            dayEls.forEach(function (el) {
                var n = parseInt(el.id.slice(1), 10);
                el.classList.toggle('done', pst.d.indexOf(n) >= 0);
                el.classList.toggle('current', n === nx);
                var b = el.querySelector('.pd-check');
                if (b) { b.setAttribute('aria-pressed', String(pst.d.indexOf(n) >= 0)); }
            });
            btnStart.hidden = !!pst.s;
            btnReset.hidden = !pst.s;
            if (pace) {
                if (!pst.s) { pace.textContent = 'Toca «Empezar» para registrar tu ritmo, o marca días directamente.'; }
                else if (!nx) { pace.textContent = 'Completaste el plan. ¡Enhorabuena!'; }
                else {
                    var expected = Math.min(totalDays, Math.floor((Date.now() - pst.s) / 86400000) + 1);
                    pace.textContent = nx === expected ? '¡Vas al día!'
                        : (nx < expected ? 'Deberías ir por el día ' + expected + ' — te falta el día ' + nx + '.'
                        : 'Vas adelantado: toca el día ' + expected + ' y ya vas en el ' + nx + '.');
                }
            }
        }
        btnStart.addEventListener('click', function () {
            pst.s = Date.now(); P.set(pkey, JSON.stringify(pst)); TK('plan', 'start:' + pslug); paintPlan();
        });
        btnReset.addEventListener('click', function () {
            pst = { s: 0, d: [] }; P.set(pkey, JSON.stringify(pst)); TK('plan', 'reset:' + pslug); paintPlan();
        });
        planApp.addEventListener('click', function (ev) {
            var b = ev.target.closest ? ev.target.closest('.pd-check') : null;
            if (!b) { return; }
            var d = parseInt(b.getAttribute('data-day'), 10);
            var i = pst.d.indexOf(d);
            if (!pst.s) { pst.s = Date.now(); }
            if (i < 0) { pst.d.push(d); TK('plan', 'day:' + pslug); } else { pst.d.splice(i, 1); }
            P.set(pkey, JSON.stringify(pst));
            paintPlan();
        });
        paintPlan();
    }
    // Índice /planes: mini-progreso por tarjeta desde localStorage
    document.querySelectorAll('[data-planprog]').forEach(function (el) {
        var s = null;
        try { s = JSON.parse(P.get('plan_' + el.getAttribute('data-planprog'), 'null')); } catch (e) {}
        if (s && Array.isArray(s.d) && s.d.length) {
            el.hidden = false;
            el.textContent = '✓ ' + s.d.length + (s.d.length === 1 ? ' día completado' : ' días completados');
        }
    });

    // ============================ Escuchar capítulo (TTS) =====================
    var vv = [].slice.call(document.querySelectorAll('.chapter .verse'));
    var lbar = null, li = 0, lstate = 'off', lutId = 0;
    var lvoices = [], lvuri = P.get('voice', '');
    var lrate = parseFloat(P.get('rate', '1')) || 1;
    var LRATES = [0.75, 1, 1.25, 1.5, 2];
    function lLang() {
        return (chapterEl && (chapterEl.getAttribute('data-pos') || '').indexOf('kjv') === 0) ? 'en' : 'es';
    }
    function loadVoices() {
        var all = speechSynthesis.getVoices();
        lvoices = all.filter(function (v) { return (v.lang || '').toLowerCase().indexOf(lLang()) === 0; });
        if (!lvoices.length) { lvoices = all; }
        renderBar();
    }
    if (vv.length && 'speechSynthesis' in window) {
        lbar = document.createElement('div');
        lbar.className = 'listenbar';
        document.body.appendChild(lbar);
        document.body.classList.add('has-listen');
        speechSynthesis.onvoiceschanged = loadVoices;
        loadVoices();
        lbar.addEventListener('click', function (ev) {
            var b = ev.target.closest('[data-l]');
            if (!b) { return; }
            var a = b.getAttribute('data-l');
            if (a === 'play') {
                if (lstate === 'pause') { speechSynthesis.resume(); lstate = 'play'; }
                else { speechSynthesis.cancel(); li = 0; speakCur(); TK('listen'); }
            } else if (a === 'pause') {
                speechSynthesis.pause(); lstate = 'pause';
            } else if (a === 'stop') {
                stopListen();
            } else if (a === 'rate') {
                var ix = LRATES.indexOf(lrate);
                lrate = LRATES[(ix + 1) % LRATES.length];
                P.set('rate', String(lrate));
                if (lstate === 'play') { lstate = 'off'; speechSynthesis.cancel(); speakCur(); }
            }
            renderBar();
        });
        lbar.addEventListener('change', function (ev) {
            if (ev.target.hasAttribute('data-lsel')) {
                lvuri = ev.target.value;
                P.set('voice', lvuri);
            }
        });
        window.addEventListener('beforeunload', function () { speechSynthesis.cancel(); });
    }

    // ---- Métricas extra: votd, navegación, export/import ----------------------
    if (document.querySelector('.votd')) {
        TK('votd', vswitch ? vswitch.getAttribute('data-version') : '');
    }
    document.addEventListener('click', function (ev) {
        var nb = ev.target.closest('a.nav-btn[rel]');
        if (nb) { TK('nav', nb.getAttribute('rel')); }
    });
    // visit_n: bucket de "día Nº del usuario" (bf_days) — una vez por día.
    try {
        var dlist = JSON.parse(P.get('days', '[]'));
        var vkey = 'vtrack:' + new Date().toISOString().slice(0, 10);
        if (localStorage.getItem('bf_' + vkey) !== '1' && dlist.length) {
            localStorage.setItem('bf_' + vkey, '1');
            var nb = dlist.length <= 3 ? String(dlist.length) : (dlist.length <= 7 ? '4-7' : (dlist.length <= 14 ? '8-14' : (dlist.length <= 30 ? '15-30' : '30+')));
            TK('visit_n', nb);
        }
    } catch (e) {}

    // read_s: segundos en el capítulo (reader) al salir/ocultar.
    var t0 = Date.now();
    window.addEventListener('pagehide', function () {
        var s = Math.round((Date.now() - t0) / 1000);
        if (chapterEl && s >= 3 && s <= 1800) { TK('read_s', 'cap', s); TK.flushNow && TK.flushNow(); }
    });

    // perf: tiempo de carga de la página (ms), agregado con contador aparte.
    window.addEventListener('load', function () {
        try {
            var pt = performance.timing;
            var ms = pt.loadEventEnd - pt.navigationStart;
            if (ms > 0 && ms < 60000) {
                TK('perf', chapterEl ? 'reader' : 'other', ms);
                TK('perf_c', '', 1);
                // US-213: histograma por buckets → p75 aproximado en el dashboard
                var b = ms < 500 ? 'a:u05' : ms < 1000 ? 'b:u1' : ms < 2000 ? 'c:u2' : ms < 4000 ? 'd:u4' : 'e:g4';
                TK('perf_b', (chapterEl ? 'reader.' : 'other.') + b, 1);
            }
        } catch (e) {}
    });
    function renderBar() {
        if (!lbar) { return; }
        var html = lstate === 'off'
            ? '<button type="button" data-l="play">▶ Escuchar</button>'
            : '<button type="button" data-l="' + (lstate === 'pause' ? 'play' : 'pause') + '">' +
              (lstate === 'pause' ? '▶' : '⏸') + '</button>' +
              '<button type="button" data-l="stop" aria-label="Detener">■</button>';
        if (lvoices.length) {
            html += '<select data-lsel aria-label="Voz" title="Voz">';
            lvoices.forEach(function (v) {
                html += '<option value="' + esc(v.voiceURI) + '"' + (v.voiceURI === lvuri ? ' selected' : '') + '>' +
                    esc(v.name.replace(/Microsoft |Google |Apple /i, '')) + '</option>';
            });
            html += '</select>';
        }
        html += '<button type="button" data-l="rate" title="Velocidad">' + lrate + '×</button>';
        lbar.innerHTML = html;
    }
    function speakCur() {
        lstate = 'play';
        vv.forEach(function (v) { v.classList.remove('speaking'); });
        var el = vv[li];
        el.classList.add('speaking');
        el.scrollIntoView({ block: 'center' });
        var u = new SpeechSynthesisUtterance(el.getAttribute('data-text') || el.textContent);
        var isEn = lLang() === 'en';
        u.lang = isEn ? 'en-US' : 'es-ES';
        var voice = lvoices.filter(function (v) { return v.voiceURI === lvuri; })[0];
        if (voice) { u.voice = voice; u.lang = voice.lang; }
        u.rate = lrate;
        var id = ++lutId;
        u.onend = function () {
            if (lstate !== 'play' || id !== lutId) { return; }
            li++;
            if (li < vv.length) { speakCur(); } else { stopListen(); renderBar(); }
        };
        speechSynthesis.speak(u);
    }
    function stopListen() {
        speechSynthesis.cancel();
        lstate = 'off'; li = 0;
        vv.forEach(function (v) { v.classList.remove('speaking'); });
    }

    // ---- Sharebar: botón copiar enlace (US-202) ------------------------------
    document.addEventListener('click', function (ev) {
        var b = ev.target.closest('[data-copy]');
        if (!b || !navigator.clipboard) { return; }
        navigator.clipboard.writeText(b.getAttribute('data-copy')).then(function () {
            var old = b.textContent;
            b.textContent = '✓';
            setTimeout(function () { b.textContent = old; }, 1200);
            TK('share', 'copy');
        });
    });

    // ---- Teclado: ←/→ capítulos · j/k versículos · Enter abre sheet · / ir a ---
    var vcur = -1;
    document.addEventListener('keydown', function (ev) {
        if (panel || sheet || (ev.target && ev.target.closest('input, textarea, select, button, a, [role="button"]'))) { return; }
        var link = null;
        if (ev.key === 'ArrowLeft') { link = document.querySelector('a[rel="prev"]'); }
        if (ev.key === 'ArrowRight') { link = document.querySelector('a[rel="next"]'); }
        if (link) { window.location.href = link.href; return; }
        if (ev.key === '/' || ev.key === 'i') {
            var gi = document.querySelector('.goto input');
            if (gi) { ev.preventDefault(); gi.focus(); gi.select(); }
            return;
        }
        if (!vv.length) { return; }
        if (ev.key === 'j' || ev.key === 'k') {
            vcur += ev.key === 'j' ? 1 : -1;
            vcur = Math.max(0, Math.min(vv.length - 1, vcur));
            vv.forEach(function (v) { v.classList.remove('focused'); });
            vv[vcur].classList.add('focused');
            vv[vcur].scrollIntoView({ block: 'center', behavior: 'smooth' });
        } else if (ev.key === 'Enter' && vcur >= 0) {
            ev.preventDefault();
            openSheet(vv[vcur]);
        }
    });

    // Versículos activables con teclado (UX-04): Enter/Espacio abre el sheet.
    // tabindex="-1" los mantiene fuera del orden de tabulación (hay ~176);
    // j/k los navega con la clase .focused y Enter desde el documento.
    vv.forEach(function (v) {
        v.setAttribute('tabindex', '-1');
        v.setAttribute('role', 'button');
        v.setAttribute('aria-haspopup', 'dialog');
        v.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter' || ev.key === ' ') {
                ev.preventDefault();
                ev.stopPropagation();
                openSheet(v);
            }
        });
        v.addEventListener('focus', function () {
            vv.forEach(function (o) { o.classList.remove('focused'); });
            v.classList.add('focused');
        });
    });
})();
