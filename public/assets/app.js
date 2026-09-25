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

    var theme = P.get('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    apply('theme', theme);
    apply('font', P.get('font', '2'));
    apply('wj', P.get('wj', 'on'));       // palabras de Jesús en rojo
    apply('vnum', P.get('vnum', 'on'));   // números de versículo
    apply('flow', P.get('flow', 'verse'));// 'verse' | 'para' (párrafo fluido)
    apply('family', P.get('family', 'serif'));

    var themeBtn = document.getElementById('themeBtn');
    if (themeBtn) {
        syncThemeBtn();
        themeBtn.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            apply('theme', next); P.set('theme', next); syncThemeBtn(); syncPanel();
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
            panel = document.createElement('div');
            panel.className = 'prefpanel';
            panel.setAttribute('role', 'dialog');
            panel.setAttribute('aria-label', 'Apariencia');
            panel.innerHTML =
                '<div class="pp-row"><span>Tema</span><div class="seg" data-k="theme">' +
                seg('light', '☀ Claro') + seg('dark', '☾ Oscuro') + seg('sepia', '◐ Sepia') + '</div></div>' +
                '<div class="pp-row"><span>Tamaño</span><input type="range" min="1" max="4" step="1" data-k="font" value="' + P.get('font', '2') + '" aria-label="Tamaño de letra"></div>' +
                '<div class="pp-row"><span>Letra</span><div class="seg" data-k="family">' + seg('serif', 'Serif') + seg('sans', 'Sans') + '</div></div>' +
                '<div class="pp-row"><span>Palabras de Jesús en rojo</span><button class="tog" data-k="wj" role="switch"></button></div>' +
                '<div class="pp-row"><span>Números de versículo</span><button class="tog" data-k="vnum" role="switch"></button></div>' +
                '<div class="pp-row"><span>Párrafo fluido</span><button class="tog" data-k="flow" data-on="para" role="switch"></button></div>';
            document.body.appendChild(panel);
            syncPanel();
            panel.addEventListener('click', function (ev) {
                var b = ev.target.closest('[data-v], .tog');
                if (!b) { return; }
                var k = b.getAttribute('data-k');
                if (b.classList.contains('tog')) {
                    var on = b.getAttribute('data-on') || 'on', off = on === 'on' ? 'off' : 'verse';
                    var cur = root.getAttribute('data-' + k);
                    var next = cur === on ? off : on;
                    apply(k, next); P.set(k, next);
                } else {
                    apply(k, b.getAttribute('data-v')); P.set(k, b.getAttribute('data-v'));
                }
                syncPanel(); syncThemeBtn();
            });
            document.addEventListener('click', outsidePanel);
        });
    }
    function seg(v, label) { return '<button type="button" data-v="' + v + '">' + label + '</button>'; }
    function syncPanel() {
        if (!panel) { return; }
        panel.querySelectorAll('.seg [data-v]').forEach(function (b) {
            b.classList.toggle('on', root.getAttribute('data-' + b.parentElement.getAttribute('data-k')) === b.getAttribute('data-v'));
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
        }
    }
    function outsidePanel(ev) {
        if (panel && !panel.contains(ev.target) && ev.target.id !== 'prefBtn') { closePanel(); }
    }
    function closePanel() {
        if (panel) { panel.remove(); panel = null; }
        document.removeEventListener('click', outsidePanel);
    }

    // ---- Switcher de versión --------------------------------------------------
    var vswitch = document.getElementById('versionSwitch');
    if (vswitch) {
        vswitch.addEventListener('change', function () {
            var cur = vswitch.getAttribute('data-version'), next = vswitch.value;
            var path = window.location.pathname.replace(/^\/+/, '');
            path = path.indexOf(cur + '/') === 0 ? next + path.slice(cur.length) : next;
            window.location.href = '/' + path + window.location.hash;
        });
    }

    // ---- Continuar donde quedé ------------------------------------------------
    var chapterEl = document.querySelector('.chapter[data-pos]');
    if (chapterEl) {
        document.cookie = 'bf_pos=' + encodeURIComponent(chapterEl.getAttribute('data-pos'))
            + ';path=/;max-age=31536000;SameSite=Lax';
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
    var sheet = null, sheetVerse = null;
    function openSheet(el) {
        closeSheet();
        sheetVerse = el;
        el.classList.add('open');
        var id = keyOf(el);
        var rec = annMap[id] || {};
        var ref = el.getAttribute('data-ref') || '';
        var text = el.getAttribute('data-text') || el.textContent.trim();

        sheet = document.createElement('div');
        sheet.className = 'vsheet';
        sheet.setAttribute('role', 'dialog');
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
            '</div>' +
            '<div class="vs-note" hidden><textarea rows="3" maxlength="2000" placeholder="Escribe tu nota…">' + esc(rec.note || '') + '</textarea>' +
            '<div class="vs-note-btns"><button type="button" data-a="save" class="va2 on">Guardar</button>' +
            (rec.note ? '<button type="button" data-a="delnote" class="va2">Borrar nota</button>' : '') + '</div></div>' +
            '</div>';
        document.body.appendChild(sheet);
        requestAnimationFrame(function () { sheet.classList.add('show'); });

        sheet.querySelector('.vs-backdrop').addEventListener('click', closeSheet);
        sheet.querySelector('.vs-x').addEventListener('click', closeSheet);

        sheet.addEventListener('click', function (ev) {
            var sw = ev.target.closest('.sw');
            var act = ev.target.closest('[data-a]');
            if (sw) {
                var c = parseInt(sw.getAttribute('data-c'), 10);
                rec.color = c || null;
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
                saveAnn(id, rec, ref);
                act.classList.toggle('on', !!rec.fav);
                paintVerse(sheetVerse, rec.color || rec.note || rec.fav ? rec : null);
            } else if (a === 'copy') {
                var payload = '“' + text + '” — ' + ref;
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(payload).then(function () {
                        act.textContent = '✓ Copiado';
                        setTimeout(function () { act.textContent = '⧉ Copiar'; }, 1100);
                    });
                }
            } else if (a === 'share') {
                var pl = '“' + text + '” — ' + ref;
                if (navigator.share) { navigator.share({ title: ref, text: pl }).catch(function () {}); }
                else { window.open('https://wa.me/?text=' + encodeURIComponent(pl), '_blank', 'noopener'); }
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
    }
    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
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
        var filter = 'all';
        var miasBase = document.querySelector('.chapter[data-pos]');
        function renderMias() {
            DB.all().then(function (list) {
                list = list.filter(function (r) {
                    if (filter === 'all') { return true; }
                    if (filter === 'hl') { return !!r.color; }
                    if (filter === 'note') { return !!r.note; }
                    if (filter === 'fav') { return !!r.fav; }
                    return true;
                }).sort(function (a, b) { return (b.ts || 0) - (a.ts || 0); });

                var box = document.getElementById('miasList');
                if (!list.length) {
                    box.innerHTML = '<p class="muted" style="padding:2rem 0;text-align:center">Aún no tienes anotaciones.<br>Toca un versículo en el lector para resaltarlo, anotarlo o marcarlo ♥.</p>';
                    return;
                }
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
        mias.addEventListener('click', function (ev) {
            var chip = ev.target.closest('[data-f]');
            var del = ev.target.closest('.mi-del');
            if (chip) {
                filter = chip.getAttribute('data-f');
                mias.querySelectorAll('[data-f]').forEach(function (c) { c.classList.toggle('on', c === chip); });
                renderMias();
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
        var impInput = document.getElementById('miasImport');
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
                    } catch (e) { alert('No pude leer ese archivo JSON.'); }
                };
                rd.readAsText(f);
                impInput.value = '';
            });
        }
        renderMias();
    }

    // ---- Flechas ←/→ ----------------------------------------------------------
    document.addEventListener('keydown', function (ev) {
        if (ev.target && /input|textarea|select/i.test(ev.target.tagName)) { return; }
        var link = null;
        if (ev.key === 'ArrowLeft') { link = document.querySelector('a[rel="prev"]'); }
        if (ev.key === 'ArrowRight') { link = document.querySelector('a[rel="next"]'); }
        if (link) { window.location.href = link.href; }
    });
})();
