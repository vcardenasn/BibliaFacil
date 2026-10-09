# Palabra Fiel — Épicas para la app nativa Android

**Estado:** AND-01 validado; AND-02 desplegado; AND-03 inicial probado en AVD; AND-04 validado end-to-end en AVD: descarga por libro, lectura en modo avión y política de licencia respetada. Pendiente teléfono físico.

## 1. Contexto actual

Palabra Fiel hoy es una aplicación web PHP 8.1+ con MySQL/PDO y frontend
HTML/CSS/JavaScript. Hay una PWA y una APK Android basada en Trusted Web Activity
(TWA): la APK abre la web mediante Chrome, no contiene una interfaz Android
reconstruida.

El repositorio incluye actualmente:

- Lectura y búsqueda de versiones locales en la base de datos.
- Algunas versiones que pueden servirse mediante API.Bible; su clave permanece
en el servidor y el servicio PHP aplica caché de corta duración y reporte FUMS.
- Notas, resaltados, favoritos, preferencias, historial y planes almacenados
localmente en el navegador (IndexedDB/localStorage), sin cuenta ni sincronización.
- Service worker web para shell, juegos, páginas visitadas y capítulos descargados.
- Endpoints JSON puntuales (`/api/contexto` y `/juegos/api/versiculo`) y un primer
slice `/api/v1/` de catálogo/capítulos locales, controlado por `FF_NATIVE_API`; no
cubre aún la experiencia completa.

La aplicación Android nativa deberá ser un cliente nuevo. Se recomienda conservar
el sitio/PWA y su HTML, y añadir una API JSON compatible con clientes móviles.

## 2. Objetivo y decisiones iniciales

**Objetivo:** publicar una app Android nativa, comunitaria, gratuita y sin anuncios,
que permita leer, buscar y conservar contenido permitido sin conexión, manteniendo
la privacidad local-first de Palabra Fiel.

**Decisión técnica:** Kotlin + Jetpack Compose en el cliente nativo; Room para
contenido/datos locales y WorkManager para descargas reanudables cuando se
implementen esas épicas. Scaffold inicial usa AGP 9.3.1, Gradle 9.5.0 y Compose
BOM 2026.04.01.

**Decisiones que deben cerrarse antes de comenzar la implementación:**

1. Acordado: la app nativa reemplazará la TWA al estar probada. Release conserva
`com.palabrafiel.app`; debug usa `.debug` para instalarse en paralelo durante el
desarrollo. Release parte de `versionCode` 2; para actualizar instalaciones TWA
será necesario firmar con el certificado compatible.
2. Versión mínima acordada: Android 6 / API 23. Definir los idiomas del primer lanzamiento.
3. Definir la lista de versiones bíblicas iniciales según licencias y cobertura.
4. Mantener las cuentas fuera del MVP. Las notas y preferencias permanecen en el
   dispositivo, sin backend de sincronización ni datos personales de usuario.
5. Acordado: cliente Android en repositorio local independiente
`../PalabraFielAndroid`; no se sube al deploy FTP del sitio.
6. Resolver revisión de políticas de Google Play, en especial la declaración del
público objetivo y las políticas aplicables a experiencias con juegos para niños.
7. **Decidido (AND-09):** el MVP infantil cubre ambos tramos por niveles de
   contenido (6-8 con acompañamiento, 9-12 autónomo); solo familias — maestros/aula
   fuera de alcance (AND-14 descartada); la gamificación temporal se mantiene sin
   cambios; la unidad piloto es una lección completa (historia + versículo +
   reflexión + actividad). Falta la prueba con familias de AND-092 para validar.

## 3. Alcance propuesto para el MVP nativo

- Catálogo de versiones y libros, lector por capítulo, navegación y selección de
versión.
- Búsqueda en las versiones que el servicio permita consultar.
- Descarga y lectura offline de versiones con licencia compatible con descarga.
- Notas, favoritos, resaltados y preferencias guardados en el dispositivo.
- Pantallas principales: Inicio, Leer, Explorar, Planes, Juegos y Mis notas.
- Accesibilidad básica, enlaces profundos y compartir referencias/versículos.
- Gestión de errores de red, descargas parciales y actualización de contenido.

**Fuera del MVP:** login/sincronización, publicación iOS, anuncios, pagos, push,
descarga masiva de versiones API.Bible sin autorización, y migración automática
invisible de datos del navegador al almacenamiento de Android.

## 4. Épicas

### AND-00 — Alcance, identidad de paquete y cumplimiento

**Prioridad:** bloqueante · **Tamaño:** S

- AND-001 Acordar si la app nativa reemplaza la TWA o se instala en paralelo.
- AND-002 Fijar `applicationId`, nombre, iconos, deep links, SDK mínimo y política
de versiones.
- AND-003 Definir el público objetivo y revisar requisitos de Google Play para
contenido bíblico y juegos infantiles antes de declarar audiencia/publicar.
- AND-004 Inventariar licencias, atribuciones, permisos de almacenamiento offline
y políticas de API.Bible para cada versión.
- AND-005 Tratar la firma como material sensible. La credencial usada para la APK
TWA se expuso en el historial de trabajo: no copiarla al nuevo proyecto ni al
repositorio. Decidir estrategia de firma y continuidad antes de distribuir una
actualización. Si se cambia el certificado del mismo `applicationId`, las
instalaciones firmadas con el certificado anterior pueden no aceptar la
actualización directa.

**Criterios de aceptación**

- Existe una decisión documentada de identidad de paquete y distribución.
- Las versiones descargables están identificadas con licencia y atribución.
- La clave privada y contraseñas no están en Git, en la APK ni en el documento.
- Las políticas de Play aplicables están revisadas antes de enviar la ficha.

### AND-01 — Fundación Android nativa

**Prioridad:** alta · **Tamaño:** M

- AND-010 Crear aplicación Kotlin con arquitectura por capas (UI, dominio,
repositorios y fuentes de datos).
- AND-011 Configurar Compose, Room, WorkManager, navegación y manejo de estados
cargando/vacío/error.
- AND-012 Crear entornos de desarrollo/producción y configuración segura de URL
base; no incluir secretos de servidor en el cliente.
- AND-013 Configurar build reproducible, análisis estático, pruebas y generación
de APK/AAB firmados desde un entorno controlado.
- AND-014 Mantener builds debug separados de los artefactos release.

**Criterios de aceptación**

- Un build limpio crea una APK debug instalable y pruebas automatizadas pasan.
- No hay claves API, credenciales FTP/DB ni contraseñas de firma en el cliente.
- El build de release es reproducible y los artefactos no se copian al docroot web
por el workflow FTP.

### AND-02 — API JSON versionada en el backend PHP

**Prioridad:** bloqueante para las pantallas nativas · **Tamaño:** L

- AND-020 Introducir `/api/v1/` sin cambiar las rutas HTML existentes.
- AND-021 Extraer serializadores/servicios de dominio reutilizando `BibleRepository`,
`ApiBibleService`, `ReadingPlan` y los servicios de juegos; evitar duplicar lógica
de negocio en controladores JSON.
- AND-022 Definir contratos JSON estables, fechas, paginación, formatos de texto,
atribución, idioma, códigos HTTP y errores.
- AND-023 Validar parámetros, límites y versiones/libros/capítulos contra catálogo;
limitar frecuencia en búsquedas y endpoints de juego.
- AND-024 Configurar límites de timeout, manejo fail-safe de proveedor externo y
logs sin textos de búsqueda, notas ni otros datos privados.
- AND-025 Mantener compatibilidad con `/api/contexto` y
`/juegos/api/versiculo` durante transición; migrarlos solo cuando el nuevo cliente
esté probado.

**Slice AND-02 desplegado** (primer alcance):

- `GET /api/v1/catalog` devuelve versiones activas locales y libros; no expone
  identificadores de API.Bible ni afirma permisos de descarga offline.
- `GET /api/v1/versions/{code}/books/{slug}/chapters/{n}` devuelve versículos y
  rangos `wj` para versiones locales.
- `FF_NATIVE_API=0` lo mantiene apagado por defecto; el hosting ya tiene el flag
  activado para esta primera entrega.
- Versiones API.Bible quedan excluidas de este primer slice hasta revisar FUMS,
  caché móvil y licencia para este canal.

**Contratos iniciales propuestos** (nombres finales a validar en AND-020):

| Endpoint | Uso |
|---|---|
| `GET /api/v1/catalog` | Versiones habilitadas, libros, orden, conteo de capítulos, idioma y atribución/licencia |
| `GET /api/v1/versions/{code}/books/{slug}/chapters/{n}` | Capítulo con versículos y marcas de palabras de Jesús cuando existan |
| `GET /api/v1/search?version=&q=&limit=&cursor=` | Búsqueda validada y paginable |
| `GET /api/v1/context?version=&book=&chapter=&verse=` | Contexto alrededor de una referencia |
| `GET /api/v1/votd?v={code}` | Versículo del día (implementado; `?date=` pendiente) |
| `GET /api/v1/topics` y `GET /api/v1/topics/{slug}?v={code}` | Catálogo temático y versículos resueltos (implementado) |
| `GET /api/v1/plans` | Definiciones de planes; los días se computan en el cliente desde el catálogo (implementado) |
| `GET /api/v1/games/verse-quiz` | Ronda para el juego de completar versículos |

Contrato orientativo de éxito:

```json
{
  "data": {
    "version": {"code": "rvr1909", "name": "Reina-Valera 1909"},
    "book": {"slug": "juan", "name": "Juan"},
    "chapter": 3,
    "verses": [{"number": 16, "text": "…", "wj": null}],
    "attribution": "…"
  },
  "meta": {"api_version": "v1"}
}
```

Errores con estructura estable y sin trazas internas:

```json
{"error": {"code": "not_found", "message": "Recurso no disponible"}}
```

**Criterios de aceptación**

- La API devuelve JSON UTF-8, contratos versionados y errores consistentes.
- Las respuestas usan exclusivamente versiones activas y permitidas.
- Peticiones inválidas no revelan SQL, rutas locales, secretos ni mensajes de
excepciones.
- El backend web continúa funcionando con sus rutas HTML y sus pruebas actuales.

### AND-03 — Catálogo, lector y navegación

**Prioridad:** alta · **Tamaño:** L

**Avance inicial (online):** catálogo y selector de versión/libro; lector de capítulos
locales, anterior/siguiente dentro del libro, atribución y estados de carga/error.
Validado en AVD; pendiente prueba en teléfono físico, navegación entre libros y preferencias.

- AND-030 Mostrar versiones disponibles y atribución/copyright de cada una.
- AND-031 Navegar por testamentos, libros y capítulos, incluyendo búsqueda de
libro y anterior/siguiente.
- AND-032 Renderizar texto con tipografía legible, selección de versión, modo
oscuro/sepia/contraste, tamaño e interlineado.
- AND-033 Implementar abrir referencia/deep link, restaurar la última posición y
compartir enlace o texto sin exponer notas privadas.
- AND-034 Respetar `wj`/letras rojas y los límites del contenido recibido.

**Criterios de aceptación**

- El lector abre referencias válidas y da errores recuperables para referencias
inválidas o capítulos no existentes.
- La atribución aparece junto al texto conforme a la licencia.
- Cambio de versión conserva libro/capítulo cuando está disponible.
- El lector es usable con TalkBack, tamaño de fuente del sistema y orientación
vertical/horizontal compatible.

### AND-04 — Descarga y lectura offline nativas

**Prioridad:** alta · **Tamaño:** L

**Validado en AVD:** `offline_download_allowed` desplegado en el catálogo; Room
guarda catálogo y capítulos; WorkManager descarga por libro y por versión
completa (~1189 capítulos, ~5 MB) con progreso, reintentos, cancelación y
reanudación. Gestor de descargas con uso de almacenamiento, borrado por libro o
total y aviso de disco lleno. En modo avión el lector abre capítulos descargados
y las versiones sin permiso no ofrecen descarga; si el campo falta, el cliente
niega la descarga por seguridad. Pendiente teléfono físico.

- AND-040 Guardar capítulos descargables en almacenamiento privado local (Room o
formato local validado), no depender del caché del service worker del navegador.
- AND-041 Descargar por libro o versión, con progreso, pausa/reanudación, cancelar,
reintentos, detección de duplicados y eliminación de contenido.
- AND-042 Persistir el manifiesto local con versión, libro, capítulos, estado,
fecha/revisión y licencia aplicable.
- AND-043 Detectar cuota/espacio insuficiente, interrupción de proceso, cambios de
red y capítulos fallidos; no marcar como completa una descarga parcial.
- AND-044 Añadir un gestor de descargas y uso de almacenamiento.
- AND-045 Permitir offline únicamente versiones con permiso explícito de descarga.
Las versiones servidas por API.Bible no se descargan en masa ni se conservan fuera
de lo permitido por su contrato.

**Criterios de aceptación**

- Una descarga puede reanudarse después de cerrar/reabrir la app y reporta
capítulos completos, fallidos y pendientes.
- El modo avión permite abrir cada capítulo descargado y distingue lo no guardado.
- Fallos parciales o falta de espacio no producen un estado de descarga completa.
- La eliminación borra el texto descargado y su manifiesto sin borrar notas/favs.
- Las reglas de retención de API.Bible se aplican también al almacenamiento nativo;
no se asume que la caché web existente autorice persistencia nativa.

### AND-05 — Datos personales locales y portabilidad

**Prioridad:** alta · **Tamaño:** M · **Estado:** completa en lo aplicable al MVP — validada en AVD

- AND-050 Implementar notas, favoritos y resaltados en almacenamiento privado local.
  *(Hecho: tabla `user_marks` en Room schema v2 con migración 1→2; diálogo por
  versículo, indicadores ♥✎ en el lector y pantalla "My notes". Las marcas no
  dependen del contenido descargado y sobreviven a su borrado — verificado en AVD.)*
- AND-051 Guardar ajustes, historial, progreso de planes, racha y puntuación local.
  *(Hecho lo aplicable hoy: `read_days` + racha con semántica PWA, `reading_history`
  con "Continuar" y `user_prefs` KV con última versión persistida — Room schema v3.
  Progreso de planes y puntuación usarán `user_prefs` cuando existan en AND-06.)*
- AND-052 Exportar e importar una copia validada con número de esquema, evitando
sobrescrituras silenciosas y avisando conflictos.
  *(Hecho: Export/Import vía SAF en "My notes"; valida tamaño, formato y campos,
  cuenta importadas/omitidas y es aditiva — no borra marcas existentes.)*
- AND-053 Permitir importar el JSON exportado desde Mis notas en la PWA. Android
no puede leer directamente el IndexedDB/localStorage del navegador: la migración
requiere acción explícita del usuario (archivo/compartir/selector de documentos).
  *(Hecho: el export Android emite el mismo `{app:"bibliafacil", v:1, ann:[…]}`
  de la PWA — el archivo es bidireccional; el color de resaltado hace round-trip
  y los devocionales PWA se omiten al importar.)*
- AND-054 Mantener privacidad local-first; no enviar contenido de notas/resaltados
ni historial identificable al backend.

**Criterios de aceptación**

- Notas y marcadores siguen disponibles tras reiniciar y actualizar la app.
- Importación/exportación valida tipo, tamaño y versión del formato antes de
modificar datos; ofrece vista previa/confirmación.
- Ningún endpoint nuevo recibe el contenido de notas en el MVP.

### AND-06 — Explorar, planes, comparación y juegos

**Prioridad:** media · **Tamaño:** L

- AND-060 Portar temas, versículo del día, planes de lectura y comparación.
  *(Hecho y validado en AVD: API v1 `topics`, `topics/{slug}?v=`, `votd?v=` y
  `plans` desplegadas; la app cachea cada respuesta en `user_prefs` para uso
  offline. Los días del plan se computan en el cliente con el mismo algoritmo
  que `ReadingPlan.php` y el progreso usa el formato `plan_{slug}` de la PWA.
  "Compare" muestra dos versiones versículo a versículo sin endpoint nuevo.)*
- AND-061 Portar los juegos priorizados, empezando por los que ya son client-side;
reutilizar bancos de contenido permitidos sin cargar vistas HTML dentro de una
pantalla nativa.
  *(Hecho y validado en AVD: los once juegos portados — vf, trivia, personaje,
  historia, memory, libros, sopa, crucigrama, versiculo (generado desde
  capítulos descargados en Room), david y paloma (canvas nativo con la misma
  física). Bancos JSON empaquetados en `res/raw`, 100% offline, mismas reglas
  de estrellas.)*
- AND-062 Adaptar rachas, XP, estrellas y progreso a almacenamiento local nativo.
  *(Hecho y validado en AVD: `bf_games` con el mismo formato de la PWA —
  `{stars:{}, plays:{}, vl:{}, vlb:{}, stickers:[], missions:{}, wk:{}}` —
  niveles por estrellas totales (8 rangos idénticos), 11 stickers con las
  mismas condiciones, misiones semanales determinísticas por semana ISO
  (+4⭐ en `_misiones`) y desafío del día por `crc32("Ymd")` con bonus ×2
  persistido en `bf_daily`.)*
- AND-063 Añadir lectura en voz alta con TextToSpeech de Android como alternativa
o complemento a Web Speech API.
  *(Implementado en el lector nativo: lectura secuencial por versículo,
  reproducir/reanudar, pausar, detener y progreso. Solo selecciona voces
  instaladas sin conexión para el idioma de la versión; se detiene al salir,
  cambiar capítulo o pasar la app a segundo plano. Fallback informativo cuando
  falta voz offline. Build/lint y fallback verificados en AVD; reproducción
  acústica pendiente de probar tras instalar una voz local, no disponible en
  el emulador usado.)*
- AND-064 Mantener controles accesibles, soporte offline donde los datos estén
disponibles, controles de audio y respeto a TalkBack/reduced motion.
  *(Mejoras implementadas en la app nativa: sopa/crucigrama anuncian
  coordenadas, letra y estado; Memory anuncia posición y estado de cartas;
  David/Paloma exponen acciones semánticas para lanzar y aletear. Se observa
  `ANIMATOR_DURATION_SCALE=0` para suprimir el rebote ambiental y los efectos
  ornamentales; se conserva el movimiento esencial de la jugabilidad. Build,
  lint y etiquetas/acciones inspeccionadas en el árbol UI del AVD. Pendiente
  prueba manual con TalkBack y el ajuste «Quitar animaciones» activados.)*

**Criterios de aceptación**

- Cada función portada usa la API versionada o datos incluidos/locales acordes a
su licencia; no depende de una WebView oculta.
- Los juegos funcionan offline solo cuando sus datos/recursos requeridos están
incluidos o descargados.
- Progreso y notas de juego permanecen en el dispositivo y pueden reiniciarse.

### AND-07 — Calidad, privacidad y operación de servicios

**Prioridad:** alta · **Tamaño:** M

- AND-070 Pruebas unitarias de dominio/serialización y pruebas instrumentadas de
navegación, almacenamiento y estados de red.
  *(Parcial: 7 pruebas JVM cubren planes, niveles, semana ISO y selección de
  misiones/desafío. 11 instrumentadas verifican decodificación JSON, round-trip
  de anotaciones con Room, migración v1→v3 preservando datos, fallback offline
  con licencia, navegación BookList→GamesHub y HTTP 200/503/timeout mediante
  conexiones simuladas. La prueba ISO encontró y corrigió `weekKey()`. Ejecutadas
  en AVD; faltan flujos completos de lector/descargas, estados de red de extremo
  a extremo y pruebas HTTP con servidor real; AND-071 cubre contratos API.)*
- AND-071 Pruebas contractuales de la API contra SQLite/MySQL y pruebas de
integración para endpoints esenciales.
- AND-072 Medir tiempos, errores y uso con métricas agregadas permitidas; respetar
Do Not Track/Global Privacy Control cuando aplique y ofrecer controles claros.
- AND-073 No incorporar SDK publicitarios, fingerprinting ni recolección de PII.
No enviar consulta completa de búsqueda, notas, texto resaltado o referencias
personales a métricas.
- AND-074 Añadir límites de request/timeout, disponibilidad degradada y alertas
operativas sin registrar secretos o contenido privado.
- AND-075 Documentar compatibilidad API/app, despliegue, rollback y retención de
caché de contenido licenciado.

**Criterios de aceptación**

- La app muestra estados de conectividad y no pierde datos locales ante un error
HTTP/timeout.
- Las métricas son agregadas, con lista permitida de eventos y dimensiones.
- Logs y errores no contienen claves, textos del usuario, notas ni datos de
anotaciones.

### AND-08 — Distribución, enlaces y mantenimiento

**Prioridad:** necesaria para lanzamiento · **Tamaño:** M

- AND-080 Generar AAB/APK de release firmado y mantener la llave fuera de Git y
del docroot; protegerla en almacenamiento controlado/secret manager.
- AND-081 Decidir Google Play, distribución directa o ambas; preparar ficha,
política de privacidad, clasificación de contenido, público objetivo y soporte.
- AND-082 Configurar Android App Links y `assetlinks.json` para enlaces de
referencias; comprobar certificado real del release.
- AND-083 Versionar la API y coordinar compatibilidad hacia atrás: el despliegue
PHP no debe romper versiones instaladas del cliente.
- AND-084 Definir versionado, notas de versión, canal de pruebas y procedimiento
para detener una release problemática.

**Criterios de aceptación**

- AAB pasa validación de firma y enlaces desde la web abren la pantalla correcta.
- Se prueba instalación limpia y actualización desde la versión anterior.
- Hay un canal de pruebas internas y un procedimiento documentado de rollback.

## Épicas propuestas AND-09–AND-14 — Experiencia infantil y familiar

Estas son propuestas de producto, no alcance aprobado para implementación. AND-09
es una puerta de decisión: no construir una nueva experiencia infantil amplia hasta
validar audiencia, recorridos y contenido con familias. Se conserva el lector adulto
y el MVP local-first sin cuentas.

### AND-09 — Descubrimiento de producto y recorridos familiares

**Prioridad:** bloqueante para la expansión infantil · **Tamaño:** S

- AND-090 Acordar tramo de edad y usuario principal: niño con acompañamiento,
  lectura autónoma por edad, o padre/maestro que facilita la actividad.
- AND-091 Mapear recorridos separados de niño y cuidador; decidir explícitamente si
  maestros y aula entran en el MVP o quedan para después.
- AND-092 Probar prototipos de baja fidelidad con familias antes de cambiar la home;
  validar comprensión, número de decisiones, lectura necesaria y regreso a la app.
- AND-093 Definir revisión editorial bíblica, nivel lector, atribución/licencia de
  texto e ilustraciones y límites de privacidad infantil antes de producir contenido.

**Criterios de aceptación**

- Quedan documentados audiencia primaria, recorridos niño/cuidador y el alcance MVP;
  la experiencia docente no se asume sin validación.
- Se registran hallazgos de pruebas con familias y decisiones que cambian o mantienen
  el prototipo; ningún flujo infantil nuevo depende de una cuenta.
- Cada unidad de contenido tiene responsable editorial, referencia y estado de
  permiso/atribución antes de incluirse en la app.

### AND-10 — Inicio y navegación child-first

**Prioridad:** alta · **Tamaño:** M · **Dependencia:** AND-09
*(Primer slice implementado: home con continuar/historia destacada/VOTD/tiles
grandes; el índice canónico queda como ruta secundaria. Paleta pastel infantil
aplicada a home, historias y detalle de lección; el espacio adulto usa un
contenedor neutro diferenciado. Falta validación con familias e ilustraciones
licenciadas en lugar de emojis.)*

- AND-100 Diseñar una home que priorice continuar, una historia/actividad breve y
  explorar por tema/personaje; conservar el índice canónico de libros como ruta
  secundaria para el lector tradicional.
- AND-101 Usar tarjetas visuales e ilustraciones licenciadas, controles grandes,
  texto breve y navegación predecible; no añadir animación decorativa por defecto.
- AND-102 Definir estados de primera visita, contenido descargable, offline, vacío,
  error recuperable y regreso desde lectura/juego.

**Criterios de aceptación**

- Niño y cuidador pueden explicar qué pueden hacer desde la home sin instrucciones
  externas, validado en pruebas de usabilidad de AND-09.
- Continuar una lectura y empezar una actividad están disponibles sin perder el
  acceso al lector por libro/capítulo.
- Flujos principales se conservan con TalkBack, ampliación de fuente, orientación
  compatible y sin conexión cuando el contenido está guardado.

### AND-11 — Primera unidad de historia y aprendizaje offline

**Prioridad:** alta · **Tamaño:** M · **Dependencia:** AND-09, AND-10
*(Primer slice implementado: `res/raw/lessons.json` con nivel, narración,
referencia, reflexión y juego vinculado; dos unidades piloto que abren el lector
y el juego relacionado. La narración puede escucharse con TTS offline
("Escuchar la historia"). Pendiente: revisión editorial con familias (AND-092) y
licencia/atribución de ilustraciones — hoy usa emoji, no assets ilustrados.)*

- AND-110 Elegir con familias una sola historia piloto; no producir una biblioteca
  extensa hasta validar que el formato enseña y se entiende.
- AND-111 Modelar cada unidad con edad/nivel lector, objetivo, narración editorial,
  referencias bíblicas, versículos de fuente permitida, una pregunta/reflexión y una
  actividad simple. Distinguir claramente texto bíblico de explicación editorial.
- AND-112 Empaquetar los metadatos y recursos requeridos localmente para la primera
  unidad; documentar idioma, licencia, atribución y texto alternativo de ilustraciones.
- AND-113 Permitir abrir las referencias en el lector nativo, respetando la versión
  disponible y sus restricciones de copia/descarga.

**Criterios de aceptación**

- La unidad piloto completa abre y se usa en modo avión tras instalar/descargar los
  recursos permitidos.
- Referencias, texto, atribución y assets pasan revisión editorial/licencias; imágenes
  informativas tienen descripción accesible y las decorativas se omiten de TalkBack.
- La prueba con familias confirma comprensión del relato y de la actividad antes de
  autorizar más unidades.

### AND-12 — Espacio para padres y cuidadores

**Prioridad:** alta · **Tamaño:** M · **Dependencia:** AND-09, AND-10
*(Primer slice implementado: sección "Para adultos" separa Descargas, Mis notas,
Temas y Planes del recorrido infantil, con tarjeta de progreso local (racha,
capítulos leídos, estrellas, notas); sin cuentas ni barrera. Pendiente decidir
barrera parental según políticas y añadir controles de contenido por nivel.)*

- AND-120 Separar visual y navegacionalmente el espacio adulto del recorrido infantil;
  acordar si requiere una barrera parental según políticas y pruebas de uso.
- AND-121 Ofrecer controles locales pertinentes: nivel lector recomendado, idioma,
  lectura en voz alta/voz disponible, descargas y acceso al lector tradicional.
- AND-122 Mostrar progreso educativo de forma descriptiva y privada; permitir exportar
  o borrar los datos locales. No enviar progreso identificable al servidor.
- AND-123 No crear cuenta, perfil con nombre/fecha de nacimiento ni sincronización en
  este MVP; cualquier excepción requiere decisión de privacidad separada.

**Criterios de aceptación**

- El adulto identifica y puede cambiar preferencias sin que el niño navegue por
  controles de sistema, licencias o mantenimiento.
- Preferencias y progreso permanecen locales, son borrables/exportables donde aplique
  y no generan PII en red ni logs.
- El acceso adulto no bloquea al niño ni usa una barrera que incumpla políticas; la
  decisión de gate queda revisada antes de distribución.

### AND-13 — Gamificación amable y centrada en aprender

**Prioridad:** resuelta por decisión (mantener mecánicas actuales) · **Tamaño:** S ·
**Dependencia:** AND-09, AND-12. Se conserva como referencia de principios para
futuras recompensas.

- AND-130 Revisar rachas, desafío diario ×2, misiones semanales y stickers actuales con
  el principio de no manipulación para niños.
- AND-131 Mantener progreso aditivo: faltar un día no quita progreso, no hay vidas que
  comprar, anuncios, avisos insistentes ni castigos por detenerse.
- AND-132 Hacer opcionales las recompensas temporales y ofrecer al adulto una
  preferencia local para ocultar rachas/desafíos si las pruebas detectan presión.
- AND-133 Priorizar señales de comprensión sobre tiempo de pantalla o sesiones diarias
  como objetivos infantiles.

**Criterios de aceptación**

- Se puede omitir un día o salir de una actividad sin perder progreso ni recibir una
  urgencia/castigo en la siguiente sesión.
- Las opciones de gamificación son comprensibles para adulto y niño, y desactivables
  donde las pruebas con familias lo indiquen.
- No se añaden métricas por niño, ranking público, monetización ni notificaciones
  persuasivas.

### AND-14 — Uso en aula y herramientas para maestros (descartada)

**Prioridad:** descartada — decisión AND-091: el producto es solo para familias ·
**Tamaño:** M · Se conserva documentada por si cambia el alcance; no implementar.

- AND-140 Confirmar con maestros de escuela dominical si una app individual resuelve
  una necesidad distinta de los recursos web `/maestros` existentes.
- AND-141 Si se valida, diseñar un modo de presentación con texto/ilustración legibles
  a distancia, selección simple de una unidad y operación offline.
- AND-142 No crear listas de alumnos, cuentas infantiles, seguimiento de clase ni
  infraestructura de aula en esta épica; reutilizar recursos existentes cuando sea
  legal y funcionalmente adecuado.

**Criterios de aceptación**

- El modo se implementa solo con una necesidad docente validada y no duplica recursos
  web sin valor adicional.
- Una sesión demostrativa funciona offline, es controlable por un adulto y no recoge
  identificadores ni progreso individual de alumnos.

## 5. Ajustes necesarios en los servicios PHP

### Backend/API

1. Añadir un módulo JSON `/api/v1/` y conservar la web HTML actual.
2. Centralizar conversión de modelos a contratos JSON; el cliente no debe depender
de los arrays internos de PHP, consultas SQL o markup.
3. Añadir `catalog` (versión/libros/capítulos/atribución) como fuente única para
que la app no codifique listas estáticas de versiones o capítulos.
4. Normalizar validación, límites, paginación, cache headers y errores.
5. Mantener el secreto `API_BIBLE_KEY` solo en `.env` del servidor. La app llama
al backend propio, nunca directamente a API.Bible con una clave embebida.
6. Revisar FUMS para el canal móvil. El backend existente consulta API.Bible y
reporta tokens; conservar esa obligación y verificar si el flujo de cliente móvil
requiere parámetros/reportes adicionales según las condiciones vigentes.
7. No habilitar descarga masiva de contenido API.Bible sin permiso escrito que la
permita; el texto bajo licencia sigue sujeto a sus reglas de caché, atribución y
retención.
8. Los datos personales permanecen locales en el MVP. Cualquier futura
sincronización requiere una épica separada de identidad, consentimiento, seguridad,
retención y eliminación de cuenta/datos.

### Operación y despliegue

- Repositorio Android separado recomendado; si comparte repo, actualizar
`.github/workflows/deploy.yml` para excluir `android/**`, `.gradle/**`, `build/**`,
APKs/AABs, keystores y archivos locales del deploy FTP.
- Añadir smoke tests de API y verificar HTTPS, encabezados, `Content-Type`,
compresión y tiempo de respuesta del hosting compartido.
- Revisar límites del hosting para consultas concurrentes/descargas; aplicar
límites del lado PHP y concurrencia prudente del cliente.
- No almacenar los datos Android de producción bajo el docroot; no agregar nuevos
secretos al `.env` salvo que una futura integración los necesite.
- Si se añaden nuevos flags `FF_*`, respetar el manejo estándar `feature_disabled`
(503) y documentar los valores requeridos en hosting.

## 6. Secuencia sugerida y dependencias

1. **AND-00** cerrar producto, audiencia/políticas, paquete, licencias y firma.
2. **AND-01** scaffold Android, build reproducible y estrategia de firma.
3. **AND-02** contratos de API y primeros endpoints; mantener intactas rutas HTML.
4. **AND-03** catálogo y lector funcional con conectividad.
5. **AND-04 + AND-05** offline licenciado y datos personales locales/portables.
6. **AND-06** mantener las funciones actuales; ampliar juegos solo con evidencia de uso.
7. **AND-09** validar edad, recorridos niño/cuidador y prototipos antes de la expansión.
8. **AND-10 + AND-11 + AND-12** diseñar la home, pilotear una unidad educativa y
   construir controles adultos mínimos según los hallazgos.
9. **AND-13** resuelta: se mantienen las mecánicas actuales; **AND-14** descartada
   (solo familias, decisión AND-091).
10. **AND-07** calidad/privacidad en paralelo; **AND-08** como gate antes del release.

La TWA y la PWA continúan publicadas durante el desarrollo. No retirar ninguna
hasta que la app nativa haya pasado pruebas de usuario, offline, accesibilidad,
actualización y distribución.

## 7. Definition of Done por historia

- Criterios de aceptación probados en el Android mínimo y en una versión reciente.
- Sin regresiones en la web: `php tests/run.php` y smoke test de rutas/API.
- Prueba manual en red lenta, sin red, almacenamiento bajo y reanudación.
- Strings traducibles y accesibles; no hardcodear contenido bíblico/licencias que
pueda cambiar en servidor.
- API documentada con ejemplo, códigos de error y compatibilidad de versiones.
- No se registran ni envían PII, búsquedas completas o anotaciones personales.
- Atribución y restricciones de licencia verificadas para datos y recursos usados.
