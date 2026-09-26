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
- US-032 ⬜ Favoritos/resaltados → **movido a EPIC 11** (IndexedDB, no localStorage)

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

## EPIC 10 — Personalización de lectura (local-first) ✅ (parcial)
*Todo sin login: localStorage + `prefers-*`. Panel "Apariencia" (⚙) con preview en vivo.*
- US-100 ✅ Temas: claro / oscuro / **sepia** / **alto contraste**
- US-101 ✅ Paleta de acento: índigo (default), oliva, terracota, púrpura, teal
- US-102 ✅ Tipografía: tamaño + familia (serif/sans) + interlineado. ⬜ OpenDyslexic + ancho columna
- US-103 ✅ Modo de lectura: versículo-por-línea ⇄ **párrafo fluido**
- US-104 ✅ Toggles: letras rojas · números · **modo zen** (oculta barras, ✕ flotante sale)
- US-105 ✅ Panel "Apariencia" (⚙ en topbar) con preview en vivo

## EPIC 11 — Anotaciones personales (local-first) ✅ (base)
*IndexedDB `bibliafacil.ann` — crece sin límite, sin cuenta. Export JSON manual.*
- US-110 ✅ **Resaltado por color** — 5 colores tipo marcador (fondo sutil del versículo, por tema)
- US-111 ✅ **Notas** por versículo: crear/editar/borrar + indicador ✎ en el texto
- US-112 ✅ **Favoritos** (♥) — marca rápida con indicador
- US-113 ✅ Vista `/mias`: filtros por tipo + buscar en notas + salto al versículo. ⬜ filtro por libro
- US-114 ✅ Exportar/importar JSON de anotaciones (backup sin cuenta)
- US-115 ✅ Sheet al tap versículo: resaltar (5 swatches) · nota · favorito · copiar · compartir. ⬜ comparar

## EPIC 12 — Compartir como imagen ✅
- US-120 ✅ Generador canvas en sheet: gradiente índigo, texto serif grande, ref + marca ✝
- US-121 ✅ Formatos historia/cuadrada/ancha → descarga PNG o Web Share (archivo)
- US-122 ⬜ Más plantillas (papel, foto de fondo) — base única índigo/dorado AA

## EPIC 13 — Hábito de lectura ✅ (base)
- US-130 ⬜ Historial de lectura reciente ("leíste ayer…") + lista de capítulos visitados
- US-131 ✅ Racha de días consecutivos — visible en /mias. ⬜ hora preferida + push PWA
- US-132 ✅ Scroll-restore: última posición dentro del capítulo por versículo
- US-133 ✅ Audio-lectura Web Speech API — barra ▶/⏸/■, resalta versículo actual. ⬜ velocidad configurable

## EPIC 14 — Estudio y comparación ⬜
- US-140 ⬜ Comparador lado a lado de 2 versiones (alineado por versículo, diff visual)
- US-141 ⬜ Versículos cruzados inline (fuentes con \x) cuando la fuente USFM los traiga
- US-142 ⬜ Contexto: expandir versículo en resultados de búsqueda (±3 versículos sin salir)
- US-143 ⬜ Copiar múltiple: selección de rango de versículos → copiar con formato (referencia + versión)

## EPIC 15 — Accesibilidad e inclusión ⬜
- US-150 ⬜ Auditoría ARIA: landmarks, anuncios live en navegación de capítulo, foco visible en todo
- US-151 ⬜ Fuente OpenDyslexic + espaciado de letras configurable (dislexia)
- US-152 ✅ Contraste alto (tema ◆) · ya respeta prefers-reduced-motion
- US-153 ✅ Teclado: `/` o `i` enfoca "Ir a" · `j`/`k` navega versículos · `Enter` abre sheet · ←/→ capítulos

## EPIC 16 — Métricas de uso (privacy-first) ✅
*Sin GA ni terceros: contadores agregados propios, anónimos, sin PII ni fingerprinting.
Beacon `track.php` + tabla de agregados diarios. Flag `FF_METRICS`.*
- US-160 ✅ Infraestructura: `metrics_daily` (metric, dim, d, n) + `metrics_dau` + `track.php` (POST beacon whitelist, sin cookies, IP nunca se guarda) + batch sendBeacon desde `app.js` — tolerante si faltan tablas
- US-161 ✅ **Adopción**: `pv` por vista (server-side en view()), DAU con hash IP+UA+día+salt rotativo en `metrics_dau` (INSERT OR IGNORE)
- US-162 ✅ **Contenido**: `cap` dim `ver:libro:cap` (top capítulos), `ver` dim código por página
- US-163 ✅ **Features**: `search`/`goto` server-side (solo conteo), `pref` dim k:v (tema/fuente/acento/wj/flow/zen…), `vswitch` dim versión — todo vía beacon
- US-164 ✅ **Engagement**: `ann` (hl/note/fav), `share` (copy/native/wa/img/imgdl), `listen`, `read_s` (segundos al pagehide)
- US-165 ✅ **Embudo**: `visit_n` con bucket desde `bf_days` (1,2,3,4-7,8-14,15-30,30+) — una vez/día vía localStorage
- US-166 ✅ Dashboard: `check.php?key=…&metrics=1` → KPIs DAU 7/30d + tablas por métrica + `&csv=1` export
- US-167 ✅ Performance: `perf` (ms por tipo de ruta) + `perf_c` muestras → avg en dashboard

### KPIs por EPIC (qué medir el éxito)
| EPIC | KPI norte | Métricas |
|---|---|---|
| 10 Apariencia | % usuarios que personalizan | toggle de tema/fuente/paleta (US-163) |
| 11 Anotaciones | anotaciones/usuario-activo | creates por tipo (US-164) |
| 12 Compartir | % sesiones con share | share por canal (US-164) |
| 13 Hábito | D7 retention | racha, visita Nº (US-165) |
| 14 Estudio | uso comparador | opens de comparar/contexto (US-163) |
| 15 Accesibilidad | adopción features accesibles | OpenDyslexic/alto contraste activados (US-163) |

### Guardrails de privacidad
- Nunca: IPs, query strings de búsqueda, contenido de notas, identificadores persistentes
- Hash de sesión rota cada 24h (irreversible, no cruza días)
- Respeto a `navigator.doNotTrack` / GPC → no se envía beacon
- Nota en footer/"Acerca de": qué se mide y por qué

## Orden sugerido (impacto/costo)
1. **US-104 + US-115** (toggle wj rojo + sheet de acciones) — ya existe infraestructura wj
2. **US-110/111/112** (anotaciones IndexedDB) — mayor valor percibido
3. **US-120** (compartir imagen) — viralidad comunitaria
4. **US-100/101/102** (panel apariencia) — bajo costo, alta percepción de "app moderna"
5. **US-160/161** (métricas básicas) — sin esto no hay forma de saber qué sigue
6. EPIC 13/14 según tracción medida

## EPIC 17 — Juegos bíblicos para niños ✅
*Sección `/juegos` dentro de BibliaFacil. Público: niños ~6-12. Visual: emoji grande +
gradientes/SVG animados + confetti canvas, sin assets pesados. Progreso (estrellas/
stickers) en localStorage — sin cuentas. Sonidos vía Web Audio API (sintetizados,
sin archivos). Mobile-first: botones gigantes, texto mínimo.*

### Motor compartido
- US-170 ✅ Hub `/juegos`: tarjetas animadas + total ⭐ + badge de nivel (🌱 Explorador → 👑 Leyenda)
- US-171 ✅ Motor `juegos.js` (BFJ): estrellas localStorage, confetti canvas, sonidos Web Audio, timer animado, shake/pop, celebración, `BFJ.define(slug)`
- US-179 ✅ Recompensas: 8 stickers por hitos (medallas ⭐, 4 juegos distintos, los 7, ronda perfecta) + álbum en hub + "🎁 ¡Sticker nuevo!" en celebración

### Los 7 juegos
- US-172 ✅ **Completa el versículo**: `VerseQuiz` PHP → `/juegos/api/versiculo?n=10` (palabra ≥5 oculta + distractores del capítulo). ⬜ niveles 2 palabras/frase
- US-173 ✅ **Trivia bíblica**: 55 preguntas × 4 categorías + mezcla, 10/ronda, timer 15s, racha
- US-174 ✅ **Ordena la historia**: 8 historias × 4-6 escenas, tap-en-orden a slots numerados
- US-175 ✅ **Memory**: 8 parejas personaje↔hazaña (14 en banco), flip 3D, ⭐ por movimientos
- US-176 ✅ **Ordena los libros**: 8 bloques del canon (66 libros desde `config/books.php`)
- US-177 ✅ **Verdadero o falso**: 28 afirmaciones con notas curiosas, timer 12s
- US-178 ✅ **Adivina el personaje**: 30 personajes × 3 pistas; pista 1 = 3⭐ … pista 3 = 1⭐

### Datos y contenido
- US-180 ✅ Bancos engordados: trivia 113 (niveles 1-3), V/F 80, historias 8, parejas 14, pistas 30
- US-181 ✅ Endpoint versículo aleatorio con distractores — solo lectura, sin PII

## EPIC 18 — SEO técnico base ✅
*`src/Core/Seo.php` genera meta por vista (description/canonical/OG/hreflang/JSON-LD/crumbs)
vía `view()` → layout. Guardrails: `/mias`, `/buscar`, `check.php`, `track.php` → `noindex`.*
- US-182 ✅ Meta dinámico: title/description únicos por vista (desc del reader = primeras 155 letras del capítulo), canonical siempre
- US-183 ✅ Open Graph + Twitter: `og:type/locale/site_name/url`, `twitter:card` (summary; large_image cuando haya og:image — US-200)
- US-184 ✅ `robots.txt` (bloquea ir/check/track/api/mias) + `/sitemap.xml` índice → `/sitemap/{ver}` (~1.256 URLs/versión) + `/sitemap/paginas`
- US-185 ✅ JSON-LD: `WebSite`+`SearchAction` en índice, `BreadcrumbList` + `Article` en reader
- US-186 ✅ Breadcrumbs visibles `.crumbs` (Inicio › Versión › Libro › Capítulo)
- US-187 ✅ hreflang es↔en (self + KJV/default) + `x-default` → versión default; `<html lang>` dinámico

## EPIC 19 — Contenido indexable (captar búsquedas) ⬜
*El lector solo captura quien ya busca "juan 3". El volumen real está en intenciones:
"versículos de ánimo", "salmo 23 explicado", "versículo del día". Cada landing =
contenido editorial real — usar métricas `cap`/`search` para priorizar.*
- US-190 ⬜ `/temas/{tema}` — colecciones curadas (amor, fe, ánimo, familia, perdón, niños…): 15-25 versículos reales + contexto. Índice `/temas`. Captura "versículos de X" — volumen altísimo
- US-191 ⬜ `/versiculo/{slug}` — landing por versículo famoso (juan-3-16, salmo-23, filipenses-4-13…): 3-4 versiones comparadas + contexto + imagen + link al capítulo
- US-192 ⬜ Intro editorial por libro en `/{version}/{libro}`: 2-3 líneas (autor, época, tema) — quita thin content del índice de capítulos
- US-193 ⬜ `/versiculo-del-dia` URL estable + archivo `?d=YYYY-MM-DD` + feed RSS — keyword "versículo del día" enorme
- US-194 ⬜ Guías: "¿Qué versión elegir?" (comparativa de nuestras 6), "Cómo empezar a leer la Biblia" — contenido comunitario + FAQ schema

## EPIC 20 — Viralidad / compartir ⬜
*Ya existe el generador canvas — falta que el preview del link (lo que se ve en
WhatsApp antes de abrir) sea una tarjeta atractiva.*
- US-200 ⬜ `og:image` dinámico por versículo: `GET /img/{v}/{libro}/{cap}/{ver}.png` con GD (gradiente + texto + ref + marca dominio) → WhatsApp/X muestran el versículo como tarjeta
- US-201 ⬜ Página compartible `/v/{ref}` (de US-191): URL corta con OG completo — la que circula en grupos
- US-202 ⬜ Share buttons visibles: WhatsApp/Telegram/X/Facebook en reader y `/temas` — no escondidos en el sheet
- US-203 ⬜ Watermark con dominio en la imagen canvas del sheet (la descarga ya existe)

## EPIC 21 — Performance / Core Web Vitals ⬜
*CWV es factor de ranking. La app ya es liviana (vanilla JS/CSS) — falta cache,
compresión y medición continua.*
- US-210 ⬜ Cache: `Cache-Control` largo en assets + fingerprint (`app.<hash>.css` o `?v=deploy`)
- US-211 ⬜ Compresión gzip/brotli en `.htaccess` + minify de assets (en deploy o pre-minificado)
- US-212 ⬜ LCP/CLS: `font-display: swap`, dimensiones reservadas, preconnect si se agregan fuentes
- US-213 ⬜ CWV medido con métrica `perf` existente → p75 en dashboard `check.php?metrics=1`

### Guardrails SEO
- `noindex`: `/mias`, `/buscar` resultados, `check.php`, `track.php`, redirects `/ir`
- Search Console: verificación por meta/archivo en `public/` + monitoreo de cobertura
- Priorizar landings con datos reales: `cap`/`search`/`share` del dashboard (EPIC 16)

## Notas técnicas
- `use` arriba en entry points + smoke test `php -S` (convención stack).
- MySQL prod / SQLite dev+tests (DB_DRIVER).
- Search: MATCH AGAINST (mysql) / LIKE (sqlite).
- Fuentes en `database/sources/` — revisar licencia antes de commitear una nueva.
