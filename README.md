# Biblia Fácil

App web para leer la Biblia en múltiples versiones — abrir → leer, sin login ni fricción.
Stack VCN: PHP 8.1+ puro, MySQL + PDO, sin framework ni Composer en runtime.

## Dev local (SQLite, sin MySQL)

```bash
cp .env.example .env          # editar: DB_DRIVER=sqlite, FF_SEARCH=1, HEALTHCHECK_TOKEN
php database/migrate.php
php database/seeds/seed.php
php scripts/import_bible.php --file=database/sources/rvr1909.json --code=rvr1909
php scripts/import_bible.php --file=database/sources/kjv.json --code=kjv
php -S 127.0.0.1:8462 -t public
```

Verificación: `php tests/run.php` · `php public/check.php` · http://127.0.0.1:8462/check.php?key=<token>

## Rutas

| Ruta | Qué |
|---|---|
| `/` | Redirige a última lectura (cookie) o default |
| `/{version}` | Índice de libros + versículo del día |
| `/{version}/{libro}` | Índice de capítulos |
| `/{version}/{libro}/{cap}` | Lector |
| `/ir?q=Juan 3:16&v=rvr1909` | Ir a referencia directa |
| `/buscar?q=&v=` | Búsqueda (FF_SEARCH) |

## Deploy — cPanel sin SSH

1. **Subdominio**: crear en cPanel (ej. `biblia.tudominio.com`), docroot → `<FTP_DIR>public/`.
2. **Secrets GitHub**: `FTP_HOST`, `FTP_USER`, `FTP_PASS`, `FTP_DIR` (dir de la app en el host, el que contiene `public/`). Push a `main` dispara el deploy FTP.
3. **`.env` en el host**: crear a mano vía File Manager (NUNCA se deploya): `APP_ENV=production`, `DB_DRIVER=mysql`, `DB_*` de la base cPanel, `HEALTHCHECK_TOKEN=<random largo>`, `FF_SEARCH=1`.
4. **Base de datos**: phpMyAdmin → importar en orden:
   - `database/schema.sql` (estructura + versions + books)
   - `database/upgrade_verses_wj.sql` (columna `wj` — solo si la tabla ya existía sin ella; con schema.sql nuevo no es necesario)
   - `database/verses_rvr1909.sql` (o el `.zip` si el límite de upload es bajo)
   - `database/verses_kjv.sql`
   - `database/verses_onbv.sql`, `verses_pddpt.sql`, `verses_v1602p.sql`, `verses_sbl.sql` (con palabras de Jesús marcadas)
   Los `verses_*.sql` son idempotentes (`INSERT IGNORE`, FKs por subquery) — re-importar no duplica.
5. **Alternativa CLI** (si prefieres el importador en lugar de phpMyAdmin): Cron Job one-shot cPanel (+2 min, borrar después):
   `php /home/<user>/<app>/scripts/import_bible.php --file=/home/<user>/<app>/database/sources/rvr1909.json --code=rvr1909 >> /home/<user>/<app>/logs/import.log 2>&1`
   o localmente con Remote MySQL habilitado con tu IP.
6. **Verificar**: `https://<host>/check.php?key=<HEALTHCHECK_TOKEN>[&fix=1]`.
   Si algo falla en el setup (404, permisos, docroot): `https://<host>/diag.php[?fix=1]`
   — diagnóstico standalone que no depende de `.env` (borrar al terminar).

## Versiones y licencias

- `config/versions.php` — catálogo; `license_status` controla visibilidad (`open`/`approved` + `active=1` se muestran).
- Importadas (dominio público / licencias libres): RVR1909, KJV (scrollmapper) y ONBV, PDDPT, V1602P, SBL (eBible.org USFM, con palabras de Jesús `\wj` → columna `verses.wj` JSON `[ini,len]`).
- ONBV = "Biblica® Open Nueva Biblia Viva 2008" (CC BY-SA 4.0) — la edición open de NBV. PDDPT = "Palabra de Dios para Ti" (CC BY 4.0). El copyright se muestra en la página de la versión (atribución obligatoria).
- RVR1960, NVI, NTV, LBLA, NBLA, DHH, TLA, PDT…: copyrighted — requieren licencia vía DBL (library.bible). Al aprobarse: `license_status=approved`, `active=1`, descargar USX/USFM e importar con `usfm2json.php` + `import_bible.php`. Ver `docs/BACKLOG.md`.
- Nunca importar texto de versiones con copyright sin licencia escrita — aunque la app sea gratis.

## Convenciones del stack

- `use` siempre arriba en entry points, antes de cualquier código ejecutable.
- Migraciones numeradas en `database/migrations/`; runner `database/migrate.php` (acepta `up` callable o SQL string; portable MySQL/SQLite).
- `scripts/build_schema.php` regenera `schema.sql` tras cambiar `config/books.php`/`versions.php`.
- `scripts/build_verses_sql.php <fuente.json> <code>` genera `database/verses_<code>.sql` (phpMyAdmin).
- `scripts/usfm2json.php <dir-usfm> <code> [nombre]` convierte USFM (eBible/DBL) → `database/sources/<code>.json` preservando `\wj` (palabras de Jesús → `[wj]` inline → rangos JSON en `verses.wj`).
- Tests: `tests/run.php` (runner custom, SQLite in-memory).
- Feature flags `FF_*` → `FeatureFlags::requireEnabled()` → 503 `feature_disabled`.
