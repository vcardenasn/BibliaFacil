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

## EPIC 16 — Métricas de uso (privacy-first) ⬜
*Sin GA ni terceros: contadores agregados propios, anónimos, sin PII ni fingerprinting.
Beacon `track.php` + tabla de agregados diarios. Flag `FF_METRICS`.*
- US-160 ⬜ Infraestructura: tabla `metrics_daily` (fecha, métrica, dimensión, contador) + endpoint `track.php` (POST beacon, sin cookies nuevas, IP nunca se guarda) + batched send desde `app.js` (navigator.sendBeacon)
- US-161 ⬜ **Adopción**: páginas vistas por ruta (lector/búsqueda/índice), sesiones únicas por día (hash diario rotativo, no persistente), DAU/MAU
- US-162 ⬜ **Contenido**: top libros/capítulos leídos, versión más usada, versículo del día visto/compartido
- US-163 ⬜ **Features**: uso de búsqueda (conteo, NO el texto de la query), "ir a", cambio de versión, tema oscuro/claro/sepia, tamaño de fuente, toggle wj rojo, modo párrafo
- US-164 ⬜ **Engagement**: anotaciones creadas por tipo (resalte/nota/favorito), compartir por canal, tiempo de lectura por capítulo (aprox: visibilitychange)
- US-165 ⬜ **Embudo de retorno**: primera visita → segunda visita → racha (localStorage cuenta visitas, beacon solo envía bucket "visita Nº")
- US-166 ⬜ Dashboard admin: `check.php?key=…&metrics=1` → resumen 7/30 días en HTML + export CSV
- US-167 ⬜ Performance: p95 de tiempo de carga por ruta (performance.timing del navegador, agregado)

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

## EPIC 17 — Juegos bíblicos para niños ⬜
*Sección `/juegos` dentro de BibliaFacil. Público: niños ~6-12. Visual: emoji grande +
gradientes/SVG animados + confetti canvas, sin assets pesados. Progreso (estrellas/
stickers) en localStorage — sin cuentas. Sonidos vía Web Audio API (sintetizados,
sin archivos). Mobile-first: botones gigantes, texto mínimo.*

### Motor compartido
- US-170 ⬜ Hub `/juegos`: tarjetas animadas por juego + tablero "mis estrellas ⭐" + badge de nivel (Explorador → Aprendiz → Maestro)
- US-171 ⬜ Motor común `juegos.js`: estado de estrellas por juego, confetti (canvas), sonidos sintetizados (acierto/error/fanfarria), timer con barra animada, shake en error, navegación `/juegos/<slug>`
- US-179 ⬜ Recompensas: stickers desbloqueables por hitos (10/50/100 ⭐, racha 5, primer juego de cada tipo) + pantalla de celebración

### Los 7 juegos
- US-172 ⬜ **Completa el versículo** ⭐ *único que usa la BD*: endpoint que devuelve versículo aleatorio de la versión activa con palabra oculta + 3 distractores del mismo capítulo. Niveles: 1 palabra / 2 / frase. Racha multiplica puntos
- US-173 ⬜ **Trivia bíblica**: banco JSON ~120 preguntas por categorías (personajes, historias, milagros, animales) × dificultad. 10 preguntas por ronda, timer, puntos por velocidad
- US-174 ⬜ **Ordena la historia**: 4-6 tarjetas-escena (emoji) drag&drop o tap-en-orden. Sets: creación, José, Noé, Pascua, sembrador, David. Estrellas por intentos usados
- US-175 ⬜ **Memory de personajes**: parejas personaje↔hazaña (emoji↔emoji) grid 4×3/4×4. Timer, estrellas por movimientos, flip animation CSS 3D
- US-176 ⬜ **Ordena los libros**: canon por bloques (Pentateuco, historia, poesía, profetas, evangelios, cartas, Apocalipsis) — tap en orden sobre tarjetas mezcladas. Datos de `config/books.php`
- US-177 ⬜ **Verdadero o falso**: ráfaga de afirmaciones con 2 botones gigantes ✓/✗, racha + comodines. Banco ~80 afirmaciones con picardía infantil ("David peleó contra un dragón")
- US-178 ⬜ **Adivina el personaje**: 3 pistas progresivas reveladas una a una; menos pistas = más ⭐. ~30 personajes con pistas curadas

### Datos y contenido
- US-180 ⬜ Bancos de contenido en `database/games/*.json` (trivia, historias, parejas, v/f, pistas) — curados a mano, español neutro
- US-181 ⬜ Endpoint versículo aleatorio con distractores (`/juegos/api/versiculo`) — solo lectura, cacheable, sin PII

## Notas técnicas
- `use` arriba en entry points + smoke test `php -S` (convención stack).
- MySQL prod / SQLite dev+tests (DB_DRIVER).
- Search: MATCH AGAINST (mysql) / LIKE (sqlite).
- Fuentes en `database/sources/` — revisar licencia antes de commitear una nueva.
