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
4. **Base de datos**: phpMyAdmin → importar `database/schema.sql` (estructura + versions + books).
5. **Versículos** (una vez, por versión):
   - Opción A — local contra MySQL remoto: habilitar Remote MySQL en cPanel con tu IP, `.env` local apuntando al host, correr `import_bible.php`.
   - Opción B — Cron Job one-shot cPanel (+2 min, borrar después):
     `php /home/<user>/<app>/scripts/import_bible.php --file=/home/<user>/<app>/database/sources/rvr1909.json --code=rvr1909 >> /home/<user>/<app>/logs/import.log 2>&1`
6. **Verificar**: `https://<host>/check.php?key=<HEALTHCHECK_TOKEN>[&fix=1]`.

## Versiones y licencias

- `config/versions.php` — catálogo; `license_status` controla visibilidad (`open`/`approved` + `active=1` se muestran).
- RVR1909 y KJV: dominio público, importadas desde `database/sources/` (formato scrollmapper).
- RVR1960, NVI, NTV, LBLA, NBLA, DHH, TLA, PDT…: copyrighted — requieren licencia vía DBL (library.bible). Al aprobarse: `license_status=approved`, `active=1`, descargar USX y adaptar/importar. Ver `docs/BACKLOG.md`.
- Nunca importar texto de versiones con copyright sin licencia escrita — aunque la app sea gratis.
- El aviso de copyright se muestra en el footer del lector (obligatorio en todas las licencias).

## Convenciones del stack

- `use` siempre arriba en entry points, antes de cualquier código ejecutable.
- Migraciones numeradas en `database/migrations/`; runner `database/migrate.php` (acepta `up` callable o SQL string; portable MySQL/SQLite).
- `scripts/build_schema.php` regenera `schema.sql` tras cambiar `config/books.php`/`versions.php`.
- Tests: `tests/run.php` (runner custom, SQLite in-memory).
- Feature flags `FF_*` → `FeatureFlags::requireEnabled()` → 503 `feature_disabled`.
