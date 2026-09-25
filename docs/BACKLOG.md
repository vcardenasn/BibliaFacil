# Biblia Fácil — Backlog

App para leer la Biblia en múltiples versiones, uso comunitario no-comercial.
Texto en BD propia (sin API en runtime). Versiones con copyright solo vía
licencia DBL (library.bible) — nunca scraping ni texto sin permiso.

Leyenda: ✅ implementado en MVP · ⬜ pendiente

## EPIC 01 — Fundaciones ✅
- US-001 ✅ Scaffold: bootstrap, autoloader PSR-4, `.env` fuera del docroot, handlers, headers+CSP
- US-002 ✅ `public/check.php` token-gated + workflow deploy FTP (SamKirkland)
- US-003 ✅ Migraciones `0001_*` + `schema.sql` importable por phpMyAdmin + `build_schema.php`

## EPIC 02 — Contenido bíblico ✅ (parcial)
- US-010 ✅ `scripts/import_bible.php` (CLI) + `scripts/build_verses_sql.php` → `database/verses_<code>.sql` importable por phpMyAdmin (INSERT IGNORE, FKs por subquery). ⬜ USX/USFM para fuentes DBL
- US-011 ✅ Seed de 66 libros con slugs + aliases ES (`config/books.php`)
- US-012 ✅ RVR1909 + KJV importadas (31.084 + 31.102 versículos). ⬜ RVG, VBL, BLM (eBible.org)
- US-013 ✅ `ReferenceParser`: "Juan 3:16", "jn 3.16-18", "1cor 13" → referencia
- US-014 ✅ `versions.license_status` (`open|requested|approved|denied`) + `active` + copyright en footer

## EPIC 03 — Lector (MVP) ✅
- US-020 ✅ Vista de lectura mobile-first, tipografía serif grande
- US-021 ✅ Selector versión (pills) + índice libro/capítulo
- US-022 ✅ Nav anterior/siguiente + flechas ←/→ teclado + cruces de libro
- US-023 ✅ "Continuar donde quedé" vía cookie `bf_pos` (redirect en `/`)
- US-024 ✅ Modo oscuro + tamaño de fuente (localStorage)
- US-025 ✅ Tap versículo → copiar / compartir (Web Share API, fallback WhatsApp)
- US-026 ✅ "Ir a" → `/ir?q=` con ReferenceParser + anchor #vN
- US-031 ✅ Versículo del día en índice de versión (rotativo determinístico)

## EPIC 04 — Búsqueda ✅ (básica)
- US-030 ✅ FULLTEXT MySQL / LIKE SQLite, `/buscar?q=&v=`, flag `FF_SEARCH`
- US-032 ⬜ Favoritos/resaltados por color (localStorage) + vista "Mis versículos"

## EPIC 05 — Planes de lectura (FF_PLANS) ⬜
- US-040 Seeds: "Biblia en un año", "NT 90 días", "Salmos+Proverbios mensual"
- US-041 UI de plan: día actual, marcar leído, progreso + racha (localStorage)

## EPIC 06 — Multi-versión avanzada ⬜
- US-050 Comparador lado a lado (2 versiones)
- US-051 ~~api.bible~~ → **superseded**: licencias DBL + import USX a BD propia

## EPIC 07 — Licencias (operación, no código)
- US-070 ⬜ Crear cuenta + organización en library.bible
- US-071 ⬜ Solicitar licencias: RVR1960, RVR1995, RVC, RVA-2015, DHH, TLA (SBU) · NTV, NBV (Tyndale) · LBLA, NBLA (Lockman) · NVI (Biblica) · PDT (Bible League)
- US-072 ⬜ Por cada aprobada: `license_status=approved`, `active=1`, descargar USX, adaptar importador, importar

## EPIC 08 — PWA (FF_PWA) ⬜
- US-080 manifest + service worker (cache capítulos visitados → lectura offline)
- US-081 Instalable (iconos, splash, standalone)

## EPIC 09 — Cuentas opcionales ⬜
- US-090 Login liviano para sincronizar marcadores/notas entre dispositivos

## Notas técnicas
- `use` arriba en entry points + smoke test `php -S` (convención stack).
- MySQL prod / SQLite dev+tests (DB_DRIVER).
- Search: MATCH AGAINST (mysql) / LIKE (sqlite).
- Fuentes en `database/sources/` — revisar licencia antes de commitear una nueva.
