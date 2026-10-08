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
| `GET /api/v1/votd?version=&date=` | Versículo del día/archivo |
| `GET /api/v1/themes` y `GET /api/v1/themes/{slug}` | Catálogo temático y referencias/textos permitidos |
| `GET /api/v1/plans` y `GET /api/v1/plans/{slug}` | Planes de lectura y lecturas por día |
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

**Prioridad:** alta · **Tamaño:** M · **Estado:** casi completa — validada en AVD; falta AND-051

- AND-050 Implementar notas, favoritos y resaltados en almacenamiento privado local.
  *(Hecho: tabla `user_marks` en Room schema v2 con migración 1→2; diálogo por
  versículo, indicadores ♥✎ en el lector y pantalla "My notes". Las marcas no
  dependen del contenido descargado y sobreviven a su borrado — verificado en AVD.)*
- AND-051 Guardar ajustes, historial, progreso de planes, racha y puntuación local.
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
- AND-061 Portar los juegos priorizados, empezando por los que ya son client-side;
reutilizar bancos de contenido permitidos sin cargar vistas HTML dentro de una
pantalla nativa.
- AND-062 Adaptar rachas, XP, estrellas y progreso a almacenamiento local nativo.
- AND-063 Añadir lectura en voz alta con TextToSpeech de Android como alternativa
o complemento a Web Speech API.
- AND-064 Mantener controles accesibles, soporte offline donde los datos estén
disponibles, controles de audio y respeto a TalkBack/reduced motion.

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

1. **AND-00** cerrar producto, paquete, licencias, firma y política de tienda.
2. **AND-01** scaffold Android, build reproducible y estrategia de firma.
3. **AND-02** contratos de API y primeros endpoints; mantener intactas rutas HTML.
4. **AND-03** catálogo y lector funcional con conectividad.
5. **AND-04 + AND-05** offline licenciado y datos personales locales/portables.
6. **AND-06** portar funciones en orden de uso observado y costo.
7. **AND-07 + AND-08** endurecimiento, pruebas, distribución y operación.

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
