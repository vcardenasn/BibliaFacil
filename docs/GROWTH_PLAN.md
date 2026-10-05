# Palabra Fiel — Plan de Crecimiento Orgánico (12 meses)

> Documento de estrategia. **No implica cambios de código todavía.**
> Elaborado a partir del análisis del repositorio, producción
> (`biblia.omni-hosting.com`) y SERPs reales en español (sep-2026).
> Toda cifra de volumen/objetivo es **hipótesis a validar** con Search
> Console (GSC) — hoy no hay datos de búsqueda propios.

---

## 0. Diagnóstico

### 0.1 Producto actual (verificado)

| Área | Estado real |
|---|---|
| Lector | 6 versiones abiertas en BD local: RVR1909, ONBV (CC BY-SA), PDDPT (CC BY), V1602P, SBL, KJV (en) — ~31 mil versículos c/u |
| Lectura | Justificado, palabras de Jesús en rojo, temas/tipografía, TTS, atajos, racha de lectura |
| Personal | Notas, resaltados, favoritos (IndexedDB, local-first, sin cuentas), export/import |
| Compartir | Sheet por versículo (copiar/nativo/WhatsApp), generador de imagen canvas (3 formatos), `og:image` dinámico GD, URL corta `/v/{libro}/{cap}/{ver}`, sharebar |
| Contenido SEO | 14 temas, 15 versículos famosos, votd + archivo + RSS, 66 intros de libro, 2 guías |
| Juegos | 7 juegos infantiles + estrellas + stickers (localStorage) |
| SEO técnico | Meta dinámico, canonical, OG/Twitter, hreflang es/en, JSON-LD, breadcrumbs, sitemap index (≈7.580 URLs), robots dinámico, dominio transparente |
| Métricas | Propias, agregadas, privacy-first (`metrics_daily`, DAU hash rotativo) + p75 de carga |
| Performance | Assets ~95 KB, cache 1 año + fingerprint; **sin gzip en el host**; TTFB 0,4–1,0 s |

### 0.2 Problemas críticos detectados (bloquean crecimiento)

| # | Hallazgo | Impacto | Evidencia |
|---|---|---|---|
| D1 | **La home hace 302 → `/rvr1909/genesis/1`** | La URL con más autoridad no tiene landing indexable; no rankea para "biblia online", "biblia fácil" | `curl -I /` |
| D2 | **Subdominio de un hosting** (`omni-hosting.com`) | Marca débil, baja confianza para backlinks/compartidos, autoridad atada a un tercero | dominio actual |
| D3 | **GSC no verificado** | Cero visibilidad de impresiones/consultas → todo es a ciegas | conversación |
| D4 | **No existen páginas por versículo indexables** | Las búsquedas más frecuentes son a nivel versículo ("salmo 91 1", "filipenses 4 13"); hoy solo hay capítulos + 15 landings | sitemap |
| D5 | `/v/…` no está en el sitemap y compite con `/versiculo/{slug}` | Canibalización potencial cuando crezca | canonical de `/v/` |
| D6 | No hay referrer/fuente de adquisición medida | No se puede calcular coeficiente viral ni qué canal trae usuarios | Metrics whitelist |
| D7 | Sin PWA / sin canal de retorno (email, push, WhatsApp) | La retención depende de que el usuario recuerde volver | layout |
| D8 | TTFB hasta ~1 s y sin compresión | Penaliza LCP en móvil 3G/4G LATAM | curl `time_starttransfer` |

### 0.3 Público objetivo (hipótesis priorizada)

| Segmento | Necesidad | Por qué nosotros | Prioridad |
|---|---|---|---|
| **A. Creyente hispanohablante móvil (25-55, LATAM + US hispano)** que comparte versículos en WhatsApp/Facebook | Encontrar un versículo por tema/ocasión y enviarlo como imagen bonita | Imagen + link sin registro, sin anuncios | **P0** |
| **B. Maestros de escuela dominical / líderes de niños** | Material y dinámicas listas para la clase | 7 juegos gratis, sin cuentas, proyectables | **P0** (nodo multiplicador: 1 maestro → 10-30 niños → familias) |
| **C. Padres cristianos** | Actividades bíblicas para hijos con pantalla "segura" | Juegos sin anuncios ni chat | P1 |
| **D. Lector nuevo / "quiero entender la Biblia"** | Biblia en lenguaje sencillo | PDDPT y ONBV usan lenguaje sencillo — ayudan a empezar sin abrumarse | P1 |
| E. Estudiante de Biblia | Comparar versiones, referencias | 6 versiones lado a lado (EPIC 14 pendiente) | P2 |

### 0.4 Problema que resuelve

"Quiero leer/compartir la Biblia **sin anuncios, sin registrarme, rápido en mi
celular**, en un español que entienda, y con algo para mis hijos."

### 0.5 Competencia (SERPs reales)

| Competidor | Fortaleza | Debilidad explotable |
|---|---|---|
| BibleGateway | Autoridad máxima, RVR1960/NVI licenciadas | UX densa, anuncios, en inglés de origen |
| YouVersion (bible.com) | App, planes, marca | Obliga a app/cuenta para lo bueno |
| bibliatodo.com | Muchas versiones, contenido masivo | Saturado de anuncios, lento |
| bibliaon.com/es | pSEO por versículo + temas | Plantillas genéricas, anuncios |
| biblia.es | Sociedad bíblica, RVR60 | UX antigua |
| **cristoestodo.org/juegos** | **28 juegos bíblicos en español** | Menos integración con lectura; hay que validar calidad |

**Restricción estructural honesta:** las versiones más buscadas (RVR1960, NVI,
NTV, LBLA) tienen copyright. **No podemos ganar "juan 3 16 rvr1960"**. Debemos
ganar en: consultas genéricas sin versión, consultas de intención
(tema/ocasión/imagen), lenguaje sencillo y niños.

### 0.6 Diferenciadores reales (defendibles)

1. **Cero anuncios, cero registro, privacidad** — ninguno de los top-5 lo ofrece.
2. **Versículo → imagen → WhatsApp en 2 toques** con dominio como watermark.
3. **"Biblia fácil de entender"**: PDDPT/ONBV + marca que coincide con la búsqueda.
4. **Biblia + juegos integrados** (juego "Completa el versículo" usa la BD real).
5. **Licencias abiertas** → podemos generar contenido derivado (imágenes, videos,
   PDF) legalmente, cosa que competidores con RVR1960 no pueden hacer libremente.

No son diferenciadores: "tener muchas versiones", "tener juegos" por sí solos.

### 0.7 Arquitectura actual y huecos

```
/                    302 ❌ (debe ser landing)
/{v}/{libro}/{cap}   ✅ ~7.500 URLs
/temas/{slug}        ✅ 14   → ampliar a 60-100
/versiculo/{slug}    ✅ 15   → ampliar a 500-1.000 (pSEO)
/versiculo-del-dia   ✅ + RSS
/guias/{slug}        ✅ 2    → ampliar a 20+
/juegos/*            ✅ 8
/v/{ref}             ✅ compartible (noindex recomendado o canonical → /versiculo)
FALTA: /ocasion/{x}, /personajes/{x}, /preguntas/{x}, /planes/{x},
       /imagenes/{tema}, /maestros, /colorear/{x}, home real
```

---

## ENTREGABLE 1 — Estrategia 12 meses

Principio rector: **primero medir y arreglar fundamentos, luego escalar
páginas, luego distribución social, luego comunidad.** Capacidad asumida:
1 owner (~8-10 h/semana) + Devin (desarrollo) + 1 revisor voluntario (doctrina).

### 0–30 días — Fundamentos y medición

| Iniciativa | Por qué podría funcionar | Hipótesis | Recursos | Riesgo | Medición |
|---|---|---|---|---|---|
| Dominio propio + 301 desde subdominio | Marca recordable aumenta CTR, retorno directo y backlinks | Un dominio de marca sube CTR orgánico ≥15% vs subdominio | ~USD 12/año, 2 h dev | Pérdida temporal de ranking en migración (bajo: hoy casi no hay ranking) | GSC: impresiones pre/post; tráfico directo |
| Verificar GSC + Bing Webmaster | Sin datos no hay estrategia | — | 1 h owner | Ninguno | Propiedad verificada, sitemap "Correcto" |
| Home real (200) | La raíz concentra autoridad | Home rankea para "biblia fácil", "biblia online sin anuncios" en 90 días | 1 día dev | Bajo | Posición/clicks de la home en GSC |
| Medición de fuente (`?s=wa`) + referrer agregado | Necesario para el coeficiente viral | Los shares WhatsApp generan ≥0,2 visitas nuevas por share | 0,5 día dev | Bajo (privacy-safe si solo se cuenta dominio) | `ref_in` en dashboard |
| Versículos pSEO fase 1 (top 300) | Consultas de versículo son el mayor volumen | 300 páginas con valor único (6 versiones + contexto + refs + imagen) indexan ≥60% | 3 días dev + dataset | Thin content si solo es texto duplicado | Páginas indexadas / impresiones |
| Canal WhatsApp + Telegram del versículo del día | WhatsApp es el canal #1 de difusión cristiana en LATAM | 100 suscriptores en 30 días con difusión manual | 15 min/día owner | Tiempo del owner | Suscriptores, clics `?s=wac` |

### 31–60 días — Contenido de intención

- **Ocasiones** (`/ocasion/cumpleanos`, `boda`, `condolencias`, `dia-de-la-madre`…) 30 páginas: consultas de alta intención e imagen. *Hipótesis:* CTR alto porque ofrecemos imagen descargable sin registro.
- **Temas** 14 → 50 con cluster interno.
- **Kit para maestros** (`/maestros`): juegos proyectables + hojas imprimibles PDF. *Hipótesis:* maestros enlazan/comparten en grupos de iglesia → backlinks naturales.
- **PWA instalable** + recordatorio local del votd.
- **Pinterest** con pines generados desde `og:image` (vertical).

### 61–90 días — Escala programática + social

- Versículos pSEO fase 2 (hasta 1.000) **solo si** fase 1 indexa ≥60% y tiene impresiones.
- Personajes bíblicos (60 páginas) conectados al juego "Adivina el personaje".
- Planes de lectura (`/planes/biblia-en-un-ano`, `nuevo-testamento-90-dias`) con progreso local → loop de retorno diario.
- Videos cortos automáticos (versículo + fondo + TTS) para Shorts/TikTok/Reels.
- Outreach de backlinks a 50 iglesias/blogs/ministerios infantiles.

### 3–6 meses — Retención y comunidad

- EPIC 14 (comparador de versiones / referencias cruzadas) — alimenta páginas de versículo más ricas.
- "Reto de 30 días" compartible (racha + insignia imagen) → loop social.
- Juegos: modo "reta a un amigo" con link (sin cuentas: seed en la URL).
- Preguntas bíblicas (`/preguntas/…`) basadas en consultas reales de GSC.
- Guías ampliadas (20+) apoyadas en datos de GSC.

### 6–12 meses — Efecto red

- UGC moderado: "mi versículo favorito y por qué" (tarjetas públicas moderadas).
- Iglesias: widget embebible "versículo del día" (backlink en cada sitio que lo instale).
- Expansión inglés (KJV ya está) y portugués si las métricas lo justifican.
- Donaciones voluntarias para cubrir hosting (sin anuncios nunca).

---

## ENTREGABLE 2 — Viral Growth Loops

### Loop 1 — Imagen de versículo en WhatsApp (núcleo)
```
Trigger:   usuario lee/busca un versículo que le toca (o una ocasión: cumpleaños)
Action:    toca versículo → "Imagen" o "Compartir"
Value:     imagen bonita + texto listo, sin registro, en 2 toques
Share:     WhatsApp estado/grupo; link /v/… con og:image (tarjeta visual)
New user:  receptor toca el link → aterriza en versículo + capítulo + temas
Repeat:    receptor comparte otro versículo / vuelve al votd
```
Hipótesis: k (invitaciones × conversión) ≥ 0,15 en fase inicial.
Medición: shares (`share:*`) vs visitas con `?s=wa` → k = visitas_nuevas_s / usuarios_que_comparten.
Riesgo: WhatsApp elimina parámetros o la imagen no se previsualiza → validar tarjeta en WA real.

### Loop 2 — Versículo del día (canal propio)
```
Trigger:   6:00 am publicación automática en Canal WhatsApp/Telegram + RSS
Action:    suscriptor lo reenvía a su familia/grupo
Value:     devocional diario sin esfuerzo
Share:     reenvío con link /versiculo-del-dia?d=…&s=wac
New user:  receptor se suscribe al canal desde la página
Repeat:    diario
```
Hipótesis: 5-10% de suscriptores reenvían diariamente. Medición: clics `s=wac`, crecimiento de suscriptores/semana.

### Loop 3 — Maestro → aula → familias (B2B2C)
```
Trigger:   maestro busca "dinámicas bíblicas para niños" / "juegos escuela dominical"
Action:    usa juegos proyectados + descarga hoja imprimible con QR
Value:     clase preparada en 5 min, gratis
Share:     niños llevan la hoja (QR → /juegos/{x}?s=qr); maestro comparte en grupo de maestros
New user:  padres/niños juegan en casa; otros maestros
Repeat:    cada domingo (ritual semanal)
```
Hipótesis: cada maestro activo genera ≥5 usuarios nuevos/mes. Medición: `ref_in:qr`, visitas a `/maestros`, descargas PDF.

### Loop 4 — Reto de juegos "¿Me superas?"
```
Trigger:   niño/adulto termina ronda con estrellas
Action:    "Reta a un amigo" → link con semilla de la ronda y puntaje
Value:     competencia sana, misma ronda para comparar
Share:     WhatsApp familiar
New user:  retado juega la misma ronda → ve su puntaje vs el retador
Repeat:    el retado reta a otro
```
Hipótesis: 10% de rondas completadas generan un reto; 30% de retos se juegan. Medición: `challenge_sent`, `challenge_play`.

### Loop 5 — SEO programático → share → backlink
```
Trigger:   búsqueda "versículos para cumpleaños" en Google
Action:    aterriza en /ocasion/cumpleanos, elige imagen
Value:     imagen personalizable (nombre opcional en cliente)
Share:     envía imagen al cumpleañero (con dominio)
New user:  receptor ve el dominio en la imagen → búsqueda de marca / visita directa
Repeat:    cada ocasión del calendario (madre, padre, navidad…)
```
Hipótesis: páginas de ocasión tienen tasa share/visita ≥8% (vs ~1-2% del lector). Medición: `share` por sección.

### Loop 6 — Racha/Reto de lectura de 30 días
```
Trigger:   usuario completa día 7/14/30 de racha o plan
Action:    genera tarjeta "Llevo 30 días leyendo la Biblia"
Value:     reconocimiento social
Share:     estado de WhatsApp / Instagram story
New user:  "¿dónde lees?" → link al plan
Repeat:    siguiente plan
```

### Loop 7 — Widget para iglesias (6-12 meses)
```
Trigger:   iglesia quiere versículo del día en su web
Action:    copia <script> del widget
Value:     contenido diario gratis
Share:     cada web muestra "vía Palabra Fiel" (backlink)
New user:  visitantes de la iglesia
Repeat:    automático diario
```

---

## ENTREGABLE 3 — SEO

### 3.1 Keyword clusters (volúmenes: validar en GSC/Keyword Planner)

| Cluster | Intención | Ejemplos long-tail | Competencia | Landing |
|---|---|---|---|---|
| **Versículo específico** | Informacional | "salmo 91 1", "filipenses 4 13 significado", "jeremías 29 11 explicación" | Alta en head, media en "significado/explicación" | `/versiculo/{slug}` |
| **Temas** | Informacional | "versículos de ánimo", "versículos de fortaleza en momentos difíciles", "versículos para la ansiedad" | Media | `/temas/{slug}` |
| **Ocasiones** | Transaccional-emocional | "versículos para cumpleaños de una hija", "versículo para boda", "versículos de condolencias", "versículos para el día de la madre" | Media, estacional | `/ocasion/{slug}` |
| **Imágenes** | Visual | "imágenes con versículos de amor", "versículos para estados de whatsapp" | Media (Pinterest domina) | `/imagenes/{tema}` |
| **Lenguaje sencillo** | Navegacional | "biblia fácil de entender", "biblia en lenguaje actual", "biblia para principiantes" | **Baja** | Home + `/guias/que-version-elegir` |
| **Niños/maestros** | Transaccional | "juegos bíblicos para niños", "trivia bíblica con respuestas", "preguntas bíblicas para niños", "dinámicas escuela dominical" | Media (cristoestodo) | `/juegos/*`, `/maestros` |
| **Personajes** | Informacional | "quién fue Noé para niños", "historia de David y Goliat resumen" | Media | `/personajes/{slug}` |
| **Preguntas** | Informacional | "cuántos libros tiene la biblia", "cuál es el versículo más corto", "quién escribió salmos" | Media | `/preguntas/{slug}` |
| **Planes** | Transaccional | "plan de lectura bíblica en un año", "leer la biblia en 90 días" | Alta | `/planes/{slug}` |
| **Libro/capítulo** | Navegacional | "génesis 1", "proverbios 3" | Alta | lector (ya existe) |

### 3.2 Landing pages nuevas

| Ruta | Cantidad inicial | Contenido mínimo para no ser thin |
|---|---|---|
| `/` home | 1 | Propuesta de valor, votd, buscador, accesos a temas/ocasiones/juegos |
| `/ocasion/{slug}` | 30 | Intro humana, 8-15 versículos, imagen por versículo, FAQ corta |
| `/personajes/{slug}` | 60 | Resumen, versículos clave, línea de tiempo, enlace al juego |
| `/preguntas/{slug}` | 40 | Respuesta directa (40-60 palabras) + desarrollo + versículos |
| `/planes/{slug}` | 5 | Calendario, progreso local, CTA recordatorio |
| `/imagenes/{tema}` | 20 | Galería de imágenes generadas (GD) con alt descriptivo |
| `/maestros` | 1 + 10 sub | Guía de uso en clase + PDF imprimibles |
| `/versiculo/{slug}` | 15 → 300 → 1.000 | Ver 3.3 |

### 3.3 Programmatic SEO

**Páginas por versículo** — la oportunidad #1, con guardrails:

- Selección: top versículos por popularidad (dataset abierto de OpenBible.info
  "popular verses"/cross-references, CC-BY — **verificar licencia antes de usar**).
- Cada página: texto en las 6 versiones, contexto (vv. anteriores/siguientes),
  referencias cruzadas, temas relacionados, imagen og, botón compartir, enlace
  a juego "Completa el versículo" con ese versículo.
- **No** publicar los 31.000 versículos: indexar solo los que superen umbral de
  valor; el resto sigue accesible por capítulo.
- Slug: `/versiculo/juan-3-16`; `/v/…` → `noindex` o canonical al slug (resolver D5).

Otras pSEO: ocasión × tema, personajes, "versículos de {tema} para {audiencia}"
(p.ej. "versículos de ánimo para mujeres") — **solo** si hay versículos
curados reales, nunca combinaciones vacías.

### 3.4 Long-tail prioritarias (ejemplos iniciales)

"versículos para cumpleaños de mamá", "versículos de ánimo para un enfermo",
"versículos para dar gracias a Dios", "salmo 23 explicado para niños",
"biblia en lenguaje sencillo online", "juego de preguntas bíblicas para niños
con respuestas", "orden de los libros de la biblia juego", "versículos cortos
para niños", "versículos para estado de whatsapp", "qué significa filipenses 4 13".

### 3.5 Internal linking

- **Hub & spoke**: `/temas` ↔ cada tema ↔ cada versículo ↔ capítulo.
- Cada versículo enlaza: 3 temas, 3 versículos relacionados, capítulo, juego.
- Cada capítulo enlaza a los versículos famosos que contiene ("Versículos destacados de este capítulo").
- Ocasiones enlazan a temas afines (cumpleaños → gratitud, bendición).
- Personajes enlazan a libros/capítulos donde aparecen + juego.
- Footer: clusters principales (ya existe parcialmente).
- Regla: ninguna página indexable a más de 3 clics de la home.

### 3.6 Schema.org

| Tipo | Dónde | Nota honesta |
|---|---|---|
| `WebSite` + `SearchAction` | Home | Ya existe |
| `BreadcrumbList` | Todas | Ya existe |
| `Article` | Versículo, tema, guía | Ya existe en parte |
| `ItemList` | Temas, ocasiones, galerías | Parcial |
| `Quotation` / `CreativeWork` (`isPartOf: Book`) | Versículos | Semántico, sin rich result garantizado |
| `FAQPage` | Preguntas | Google limita rich results FAQ desde 2023 — útil semánticamente, no esperar snippet |
| `ImageObject` (con `license`, `creditText`) | Imágenes | Puede habilitar badge "licenciable" en Google Imágenes |
| `VideoObject` | Si se embeben Shorts | Fase 3+ |
| `LearningResource` / `Game` | Juegos, maestros | Semántico |
| `Organization` + `logo` + `sameAs` | Home | Consolida marca con redes |

### 3.7 Content clusters (pilares)

1. **"Versículos de la Biblia por tema"** (pilar `/temas`) → 50 temas → 500+ versículos.
2. **"Versículos para cada ocasión"** (pilar `/ocasion`) → 30 ocasiones.
3. **"Aprender la Biblia fácil"** (pilar `/guias`) → versiones, cómo leer, planes, preguntas.
4. **"Biblia para niños"** (pilar `/juegos` + `/maestros` + `/personajes`).

### 3.8 Backlink strategy

| Táctica | Por qué | Esfuerzo | Riesgo |
|---|---|---|---|
| Recursos para maestros (PDF + juegos) → blogs de ministerio infantil | Contenido "enlazable" genuino | Medio | Bajo |
| Widget votd para iglesias | Backlink por instalación | Medio (dev) | Bajo |
| Directorios cristianos y listas "recursos bíblicos gratis" | Rápido, relevancia temática | Bajo | Bajo (evitar granjas) |
| Aparecer en listas "apps/sitios bíblicos sin anuncios" (outreach a bloggers) | Diferenciador claro | Medio | Bajo |
| Proyecto open-source (GitHub público: parser USFM, generador de imágenes) | Enlaces de devs + credibilidad | Bajo | Bajo |
| Atribución de licencias CC (eBible.org, etc.) — pedir listado de "sitios que usan" | Relación natural | Bajo | Bajo |
| **No** comprar enlaces / PBN | — | — | Penalización |

---

## ENTREGABLE 4 — Content Engine

### 4.1 Tipos de contenido y frecuencia

| Tipo | Frecuencia | Fuente | IA | Revisión humana |
|---|---|---|---|---|
| Versículo del día (web + canales) | Diario (automático) | BD | No (determinista) | Semanal (lista curada 30 días) |
| Imagen votd (1:1, 9:16, 2:3) | Diario | GD/canvas | No | Muestreo |
| Video corto (texto animado + TTS) | 3-5/semana | BD + ffmpeg | TTS | Sí antes de publicar |
| Página de tema/ocasión | 2/semana | Curación | Borrador de intro | **Sí obligatoria** |
| Página de versículo pSEO | Lotes de 50 | BD + refs cruzadas | Resumen de contexto (borrador) | Muestreo 20% + checklist |
| Personaje / pregunta | 2/semana | Curación | Borrador | **Sí obligatoria** |
| Hoja imprimible maestros | 1/semana | Juegos | Ideas de actividad | Sí |
| Post Reddit/Facebook grupos | 2/semana | Contenido propio | Adaptación | Sí (tono comunidad) |

### 4.2 Plantillas

- **Tema/Ocasión**: H1 "Versículos de {X}" · intro 80-120 palabras (humana) · lista de versículos con imagen · "Cómo usar estos versículos" · temas relacionados · FAQ 2-3.
- **Versículo**: H1 "{Ref} — texto y significado" · texto en 6 versiones · contexto · "qué enseña" (≤120 palabras, revisado) · referencias cruzadas · temas · imagen · compartir · juego.
- **Personaje**: resumen para niños (60 palabras) + para adultos (150) · versículos clave · línea de tiempo · juego.
- **Pregunta**: respuesta directa en el primer párrafo · desarrollo · versículos que la sustentan.

### 4.3 Automatización / IA

- Determinista (sin IA): votd, imágenes, sitemaps, canales, RSS, videos base.
- IA generativa **solo para borradores** (intros, resúmenes, adaptación social),
  con prompt que obliga a citar versículos existentes y prohíbe doctrina
  denominacional. Modelos: cualquier LLM vía API usado **offline en scripts**
  (no en runtime), salida a archivo `config/*.php` revisable en PR.
- Revisión humana: checklist doctrinal neutral (no afirmar interpretaciones
  denominacionales como hechos), exactitud de citas (validación automática
  contra BD), tono, licencia.

### 4.4 Distribución

Web (indexable) → RSS → Canal WhatsApp/Telegram → Pinterest (auto) → Shorts/TikTok/Reels
(semi-auto) → Facebook grupos/páginas (manual) → Reddit (manual, selectivo).

---

## ENTREGABLE 5 — Social Distribution (1 pieza → N formatos)

Pieza fuente de ejemplo: **`/ocasion/dia-de-la-madre`** (12 versículos).

| Canal | Formato específico | Por qué ese formato | Frecuencia | CTA |
|---|---|---|---|---|
| **Facebook** | Página: álbum de 5 imágenes 1:1 + texto emocional corto; Grupos cristianos: 1 imagen + pregunta "¿Cuál le enviarías a tu mamá?" | FB premia conversación; grupos son donde está el público 35-60 | Página 1/día, grupos 2/semana | Link en comentario (no en post) |
| **Instagram** | Carrusel 7 slides (portada gancho → 5 versículos → CTA "guárdalo"); Stories diarias votd con sticker link | Carruseles se guardan/comparten; stories con link a web | Carrusel 3/semana, stories diario | "Link en bio / sticker" |
| **TikTok** | Video 12-20 s: texto que aparece palabra a palabra sobre fondo en movimiento + voz TTS cálida + música libre; gancho 1ª línea "Envíale esto a tu mamá hoy" | Retención por texto dinámico; tendencia "envíale esto a…" | 1/día | "Más en el link del perfil" |
| **YouTube Shorts** | Mismo master vertical, título SEO ("Versículo para el Día de la Madre ❤️ Proverbios 31"), descripción con link | Shorts indexa en YouTube/Google; títulos buscables | 1/día (reuso) | Link en descripción |
| **Pinterest** | Pin vertical 2:3 por versículo, título keyword ("Versículos para el día de la madre — imagen"), board por tema/ocasión, link a la landing | Pinterest es buscador visual; tráfico de larga cola durante años | 5-10/día automáticos | Click al pin → landing |
| **Reddit** | **No** autopromoción: aportar en r/Cristianismo, r/Christianity (en), r/sundayschool (en) respondiendo preguntas con versículos y, cuando aplique, enlazar recurso gratuito (maestros) | Reddit castiga spam; valora recursos genuinos | 1-2/semana | Link solo si responde la pregunta |
| **X** | Hilo corto: versículo + contexto histórico en 3 tuits; votd diario con imagen 16:9 | X favorece texto/conversación | 1/día | Link al final del hilo |
| **LinkedIn** | Historia de construcción ("Construí una Biblia sin anuncios ni registro para la comunidad" — build in public, tech + propósito) | Público de líderes/devs/donantes; backlinks y colaboradores | 2/mes | Link al repo/sitio |

Regla: cada pieza web produce **mínimo 1 master vertical (9:16), 1 cuadrado,
1 vertical 2:3 y 1 texto** — todos generables desde la BD con plantillas.

---

## ENTREGABLE 6 — Backlog de implementación

Esfuerzo: S ≤ 0,5 día · M 1-2 días · L 3-5 días · XL > 1 semana.
Responsable: **O** = owner, **D** = Devin (dev), **R** = revisor voluntario.

### EPIC G1 — Fundamentos de medición y marca (P0)

| ID | User story | Tasks | Acceptance criteria | Prio | Dep | Esf | KPI |
|---|---|---|---|---|---|---|---|
| G1-1 | Como owner quiero ver impresiones y consultas en Google | Verificar GSC (archivo HTML en `public/`), enviar sitemap; Bing Webmaster | Propiedad verificada; sitemap "Correcto"; Bing importado | P0 | — | S | Páginas indexadas |
| G1-2 | Como marca quiero un dominio propio | Comprar dominio; apuntar a cPanel; 301 host-based del subdominio al dominio; GSC "cambio de dirección" | `curl -I` subdominio → 301 al dominio conservando path; canonical/OG usan dominio nuevo | P0 | decisión O | S | Tráfico directo, CTR |
| G1-3 | Como visitante quiero una home útil | Vista `home.php` (200): propuesta, votd, buscador, clusters, juegos; SEO completo | `/` responde 200; title/description únicos; JSON-LD WebSite+Organization | P0 | — | M | Clics a home en GSC |
| G1-4 | Como owner quiero saber de dónde llegan los usuarios | Param `?s=` en todos los links compartidos; métrica `ref_in` (fuente) y `ref_dom` (dominio del referrer, solo host) | Dashboard muestra visitas por fuente; sin IP ni URL completa guardadas | P0 | — | S | k viral, visitas por canal |
| G1-5 | Resolver canibalización `/v/` | `/v/` → `noindex,follow` o canonical a `/versiculo/{slug}` cuando exista | Solo una URL indexable por versículo | P0 | G2-1 | S | Duplicados en GSC = 0 |
| G1-6 | Reducir TTFB | Perfilar consultas del lector; índices; cache de página HTML en `cache/` para anónimos | TTFB p75 < 400 ms en lector | P1 | — | M | p75 perf |

### EPIC G2 — pSEO de versículos (P0)

| ID | User story | Tasks | AC | Prio | Dep | Esf | KPI |
|---|---|---|---|---|---|---|---|
| G2-1 | Como buscador quiero una página por versículo popular | Dataset popularidad + refs cruzadas (verificar licencia); tabla/config de versículos; ruta genérica `/versiculo/{libro}-{cap}-{ver}` | 300 páginas 200 con 6 versiones, contexto, 3+ refs, temas, imagen | P0 | licencia dataset | L | % indexado ≥60% a 30 días |
| G2-2 | Evitar thin content | Umbral de calidad (≥2 versiones + ≥3 refs + contexto); noindex si no cumple | Páginas bajo umbral con `noindex` | P0 | G2-1 | S | Cobertura sin "rastreada no indexada" masiva |
| G2-3 | Enlazar desde capítulos | Bloque "versículos destacados" en lector | Cada capítulo con versículos pSEO los enlaza | P1 | G2-1 | S | Páginas por sesión |
| G2-4 | Escalar a 1.000 | Lote 2 condicionado a métricas fase 1 | Decisión documentada con datos GSC | P1 | G2-1 + 30 días | M | Impresiones |
| G2-5 | "Qué enseña" por versículo | Script IA borrador → revisión R → config | 100% revisado antes de publicar | P2 | G5-2 | L | CTR, tiempo en página |

### EPIC G3 — Contenido de intención (P0/P1)

| ID | User story | Tasks | AC | Prio | Dep | Esf | KPI |
|---|---|---|---|---|---|---|---|
| G3-1 | Ocasiones | `config/ocasiones.php` 30 ocasiones; vista; sitemap; SEO | 30 URLs 200 con ≥8 versículos + imágenes + sharebar | P0 | — | L | Shares/visita ≥8% |
| G3-2 | Temas 14→50 | Curación + intros revisadas | 50 temas, cada uno ≥10 versículos | P1 | R | L | Impresiones cluster |
| G3-3 | Personajes | 60 personajes, enlace al juego | Página + JSON-LD + enlace juego | P1 | R | L | Clics GSC |
| G3-4 | Preguntas | 40 preguntas desde GSC + manual | Respuesta directa en primer párrafo | P1 | G1-1 datos | L | Posición media |
| G3-5 | Planes de lectura | 5 planes, progreso local, recordatorio | Progreso persiste; share al completar | P1 | G4-2 | L | Usuarios recurrentes |
| G3-6 | Galerías de imágenes | `/imagenes/{tema}` con ImageObject + license | 20 galerías; alt descriptivo | P2 | G3-1 | M | Tráfico Google Imágenes |
| G3-7 | Calendario estacional | Pre-publicar 45 días antes: madre, padre, navidad, semana santa, año nuevo | Páginas indexadas antes del pico | P1 | G3-1 | S | Clics en pico |

### EPIC G4 — Loops virales y retención (P0/P1)

| ID | User story | Tasks | AC | Prio | Dep | Esf | KPI |
|---|---|---|---|---|---|---|---|
| G4-1 | Canal WhatsApp/Telegram votd | Crear canales; script que genera texto+imagen del día; publicación (Telegram automático por bot; WhatsApp manual/asistido) | Publicación diaria 6:00 sin fallos 30 días | P0 | G1-4 | M | Suscriptores, clics `s=wac` |
| G4-2 | PWA | manifest, service worker (cache offline de assets + último capítulo), prompt instalar | Lighthouse "installable"; funciona offline el último capítulo | P1 | — | M | Instalaciones, retorno |
| G4-3 | Reta a un amigo (juegos) | Semilla determinista en URL; pantalla de comparación | Mismo set de preguntas para ambos; métrica challenge_* | P1 | — | M | Retos enviados/jugados |
| G4-4 | Tarjeta de racha | Imagen "N días leyendo" + share | Se genera en 7/14/30 | P2 | — | S | Shares racha |
| G4-5 | Kit maestros | `/maestros`, PDFs imprimibles con QR `?s=qr`, modo proyector en juegos | 10 PDFs; QR medible | P0 | G1-4 | L | Visitas `s=qr`, backlinks |
| G4-6 | Widget iglesias | `<script>`/iframe votd con "vía Palabra Fiel" | Instalable con 1 línea; no rompe sitios host | P2 | G1-2 | M | Dominios referentes |

### EPIC G5 — Content engine y automatización (P1)

| ID | User story | Tasks | AC | Prio | Dep | Esf | KPI |
|---|---|---|---|---|---|---|---|
| G5-1 | Render masivo de imágenes | Script CLI: formatos 1:1, 9:16, 2:3 por versículo/tema | Lote de 100 imágenes en < 2 min | P1 | — | S | Pines publicados |
| G5-2 | Pipeline de borradores IA | Script offline → `drafts/` → validación de citas vs BD → revisión → config | 0 citas inexistentes; aprobación R registrada | P1 | — | M | Piezas/semana |
| G5-3 | Videos cortos | ffmpeg: fondo + texto animado + TTS (voz con licencia comercial libre) | MP4 9:16 de 15 s válido para TikTok/Shorts | P2 | G5-1 | L | Vistas, clics perfil |
| G5-4 | Pinterest auto | Feed RSS de imágenes o API → boards | 5-10 pines/día | P2 | G5-1 | M | Clics salientes Pinterest |

### EPIC G6 — Backlinks y comunidad (P1/P2)

| ID | User story | Tasks | AC | Prio | Dep | Esf | KPI |
|---|---|---|---|---|---|---|---|
| G6-1 | Lista de outreach | 100 prospectos (blogs ministerio infantil, iglesias, directorios) | Hoja con contacto y estado | P1 | G4-5 | M | Respuestas |
| G6-2 | Outreach mensual | 25 contactos/mes con recurso concreto | Tasa respuesta ≥10% | P1 | G6-1 | M | Dominios referentes |
| G6-3 | Repo público | Publicar código (sin secretos) + README | Repo público enlaza al sitio | P2 | revisión secretos | S | Backlinks GitHub |
| G6-4 | UGC moderado | "Mi versículo favorito" con moderación previa | Nada se publica sin aprobación | P3 | cuentas/antispam | XL | Envíos |

### EPIC G7 — Métricas (P0)

| ID | User story | Tasks | AC | Prio | Dep | Esf | KPI |
|---|---|---|---|---|---|---|---|
| G7-1 | Dashboard growth | Sección en `check.php` con fuentes, k viral, share rate por sección, retorno | Una pantalla con los KPIs del Entregable 9 | P0 | G1-4 | M | — |
| G7-2 | Import GSC | Export mensual CSV → tabla o hoja | Tendencia mensual visible | P2 | G1-1 | S | — |

---

## ENTREGABLE 7 — Primeros 30 días

| Día | Acción | Resp | Resultado esperado | KPI | Dependencias |
|---|---|---|---|---|---|
| 1 | Verificar GSC + enviar sitemap; Bing Webmaster | O+D | Propiedades activas | Sitemap "Correcto" | archivo/token de Google |
| 2 | Decidir y comprar dominio | O | Dominio registrado | — | presupuesto |
| 3 | DNS + addon domain en cPanel; SSL | O | Dominio sirve la app | 200 en HTTPS | D2 |
| 4 | 301 host-based subdominio → dominio; cambio de dirección en GSC | D+O | Migración completa | 301 correcto | D3 |
| 5 | Home real (G1-3) | D | `/` 200 indexable | Home en sitemap | — |
| 6 | `?s=` + `ref_in`/`ref_dom` (G1-4) | D | Fuentes medibles | Dashboard con fuentes | — |
| 7 | Crear Canal WhatsApp + Telegram; bio/links | O | Canales listos | 0→20 suscriptores | D6 |
| 8 | Verificar licencia dataset refs cruzadas/popularidad | O+D | Decisión go/no-go | Licencia documentada | — |
| 9-11 | pSEO versículos fase 1: modelo + 300 páginas (G2-1/2) | D | 300 URLs de calidad | URLs en sitemap | D8 |
| 12 | `/v/` noindex/canonical (G1-5) + enlaces desde capítulos (G2-3) | D | Sin canibalización | 0 duplicados | D11 |
| 13 | Publicación diaria votd en canales (inicia ritual) | O | Rutina diaria | Clics `s=wac` | D7 |
| 14 | Revisión semana 2: cobertura GSC, errores | O+D | Lista de fixes | % indexado | D1 |
| 15-17 | Ocasiones: 15 primeras + imágenes (G3-1) | D+R | 15 landings | Shares/visita | — |
| 18 | Pre-publicar próxima fecha estacional del calendario | D | Página lista 45 días antes | Indexada | D17 |
| 19 | Kit maestros: página `/maestros` + 3 PDFs con QR | D+O | Primer recurso enlazable | Descargas | D6 |
| 20 | Pinterest: cuenta business, 5 boards, 30 pines iniciales | O | Presencia visual | Impresiones Pinterest | D17 |
| 21 | Revisión semana 3: fuentes, shares, top páginas | O | Ajustes de prioridad | k viral inicial | D6 |
| 22-23 | Ocasiones restantes (15) | D+R | 30 ocasiones | Impresiones cluster | D17 |
| 24 | Lista de 50 prospectos de backlinks | O | Hoja de outreach | — | D19 |
| 25 | Enviar 15 correos de outreach (recurso maestros) | O | Primeros contactos | Respuestas | D24 |
| 26 | Script render masivo de imágenes (G5-1) | D | 100 imágenes/lote | — | — |
| 27 | Instagram/Facebook: 3 carruseles desde ocasiones | O | Primeros posts | Guardados/compartidos | D26 |
| 28 | TTFB: perfilado + índices (G1-6 parcial) | D | Lector más rápido | p75 perf | — |
| 29 | Publicar post LinkedIn "build in public" | O | Visibilidad/colaboradores | Visitas LinkedIn | — |
| 30 | Retro mensual: KPIs vs objetivos; priorizar mes 2 | O+D | Plan mes 2 ajustado a datos | Dashboard completo | todo |

---

## ENTREGABLE 8 — Automatización

| # | Input | Process | AI/Tool | Output | Validation | Destination |
|---|---|---|---|---|---|---|
| A1 | Fecha | Elegir votd de lista curada | PHP (determinista) | Texto + imagen 1:1/9:16 | Revisión semanal de la lista | Web, RSS, Telegram bot, WhatsApp (asistido) |
| A2 | Lista de versículos | Render GD en 3 formatos | PHP GD | PNG por versículo | Muestreo visual 5% + tamaño > 0 | `/imagenes`, Pinterest, IG |
| A3 | Imagen + texto | Composición de video | ffmpeg + TTS con licencia | MP4 9:16 15 s | Revisión humana antes de subir | TikTok, Shorts, Reels |
| A4 | Tema/ocasión | Borrador de intro/FAQ | LLM vía script offline | Markdown/PHP array en `drafts/` | Validar citas contra BD (script) + revisión R | `config/*.php` vía PR |
| A5 | Consultas GSC (CSV) | Clustering de consultas sin página | Script + LLM para agrupar | Lista de páginas a crear | Revisión O | Backlog |
| A6 | Página publicada | Adaptación por canal (Entregable 5) | LLM con plantillas por canal | Copys FB/IG/X/Pinterest | Revisión O (tono) | Programador de posts |
| A7 | Sitemap/URLs | Chequeo de salud diario | Script curl (cron) | Reporte 4xx/5xx, títulos duplicados | Alerta si > 0 errores | Log / email |
| A8 | Métricas diarias | Resumen semanal | Script PHP | KPIs semana vs anterior | — | Email / dashboard |
| A9 | Dataset refs cruzadas | Import y filtrado por votos | Script PHP | Tabla `crossrefs` | Conteos y muestreo | BD |

Regla: **la IA nunca publica directamente**. Todo contenido teológico pasa por
validación automática de citas + revisión humana.

---

## ENTREGABLE 9 — Métricas

| KPI | Fuente | Objetivo mes 1 | Mes 3 | Mes 6 | Mes 12 | Cómo medir |
|---|---|---|---|---|---|---|
| Organic sessions | Métricas propias (`ref_in:google`) + GSC clicks | línea base | 1.000/mes | 6.000/mes | 25.000/mes | `ref_dom` agregado |
| Users (únicos) | `metrics_dau` | línea base | 1.500/mes | 8.000/mes | 35.000/mes | Suma DAU (aprox, hash diario) |
| CTR orgánico | GSC | — | ≥3% | ≥4% | ≥5% | GSC rendimiento |
| Search impressions | GSC | registrar | 50k/mes | 300k/mes | 1,2M/mes | GSC |
| Search clicks | GSC | registrar | 1.000 | 8.000 | 40.000 | GSC |
| Indexed pages | GSC cobertura | ≥1.000 | ≥3.000 | ≥6.000 | ≥8.500 | GSC |
| Keywords (top 10) | GSC | — | 50 | 400 | 2.000 | GSC consultas posición ≤10 |
| Backlinks (dominios referentes) | GSC enlaces / Ahrefs free | 0-3 | 10 | 30 | 80 | GSC "Enlaces" |
| Conversion rate* | Métricas propias | línea base | 15% | 20% | 25% | *Acción de valor / sesión: share, juego terminado, plan iniciado, instalación PWA |
| Returning users | `visit_n` ≥2 | línea base | 20% | 28% | 35% | bucket visit_n |
| Shares | `share:*` | línea base | 300/mes | 2.000/mes | 10.000/mes | Métricas propias |
| Referrals (k viral) | `ref_in:wa/wac/qr` / usuarios que comparten | medir | 0,15 | 0,25 | 0,35 | Fórmula en dashboard |
| Revenue | Donaciones (opcional) | 0 | 0 | cubrir hosting | cubrir hosting + dominio | Plataforma de donación |

Objetivos = hipótesis de partida con dominio nuevo y baja autoridad; se
recalibran en la retro del día 30 y del día 90 con datos reales de GSC.

---

## Decisiones pendientes del owner

1. **Dominio propio**: ¿se compra? (nombre sugerido a validar disponibilidad: variantes de "bibliafacil").
2. **Capacidad real** semanal del owner (el calendario asume 8-10 h/semana).
3. **Revisor doctrinal voluntario**: ¿quién? (bloquea G3/G2-5).
4. **Postura sobre IA** en contenido (propuesta: solo borradores con revisión).
5. **Donaciones**: ¿sí/no y cuándo?
