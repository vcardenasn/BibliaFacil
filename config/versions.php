<?php

// Versiones soportadas. license_status:
//   open      → dominio público / licencia libre, importable ya
//   requested → licencia pedida (DBL / publisher), pendiente
//   approved  → licencia otorgada, importar fuente autorizada
//   denied    → no disponible, no mostrar
// Solo `open` y `approved` con `active=1` aparecen en la app.

return [
    [
        'code' => 'rvr1909',
        'name' => 'Reina-Valera 1909',
        'language' => 'es',
        'copyright' => 'Dominio público',
        'license' => 'public_domain',
        'license_status' => 'open',
        'source_url' => 'https://ebible.org/sparvr/',
        'active' => 1,
    ],
    [
        'code' => 'kjv',
        'name' => 'King James Version',
        'language' => 'en',
        'copyright' => 'Public Domain',
        'license' => 'public_domain',
        'license_status' => 'open',
        'source_url' => 'https://ebible.org/eng-kjv2006/',
        'active' => 1,
    ],
    [
        'code' => 'onbv',
        'name' => 'Biblica® Open Nueva Biblia Viva 2008',
        'language' => 'es',
        'copyright' => '© 2006, 2008 Biblica, Inc. — CC BY-SA 4.0',
        'license' => 'cc-by-sa-4.0',
        'license_status' => 'open',
        'source_url' => 'https://ebible.org/spaonbv/',
        'active' => 1,
    ],
    [
        'code' => 'pddpt',
        'name' => 'Palabra de Dios para Ti',
        'language' => 'es',
        'copyright' => '© 2020 Asociación Bíblica Latinoamericana — CC BY 4.0',
        'license' => 'cc-by-4.0',
        'license_status' => 'open',
        'source_url' => 'https://ebible.org/spapddpt/',
        'active' => 1,
    ],
    [
        'code' => 'v1602p',
        'name' => 'Valera 1602 Purificada',
        'language' => 'es',
        'copyright' => '© 2007-2024 Iglesia Bautista Bíblica de la Gracia — distribución gratuita',
        'license' => 'free-distribution',
        'license_status' => 'open',
        'source_url' => 'https://ebible.org/spav1602p/',
        'active' => 1,
    ],
    [
        'code' => 'sbl',
        'name' => 'Santa Biblia Libre Latinoamericano',
        'language' => 'es',
        'copyright' => 'Dominio público',
        'license' => 'public_domain',
        'license_status' => 'open',
        'source_url' => 'https://ebible.org/spabll/',
        'active' => 1,
    ],
    // Licenciadas vía API.Bible (plan del usuario: NTV, NBLA).
    // Cuando el plan las habilite: poner 'api_bible_id' => '<id>' (ver
    // scripts/apibible_list.php), license_status='approved' y active=1.
    // NO se descargan a la BD — el lector las sirve por API con caché
    // temporal + reporte FUMS (ToS §10/§11/§14). Se excluyen de robots/sitemap
    // para proteger la cuota mensual (5,000 llamadas Starter).
    ['code' => 'rvr1960', 'name' => 'Reina-Valera 1960',            'language' => 'es', 'copyright' => '© 1960 Sociedades Bíblicas en América Latina; © renovado 1988 Sociedades Bíblicas Unidas', 'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'active' => 0],
    ['code' => 'nvi',     'name' => 'Nueva Versión Internacional',  'language' => 'es', 'copyright' => '© Biblica, Inc.',          'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'active' => 0],
    ['code' => 'ntv',     'name' => 'Nueva Traducción Viviente',    'language' => 'es', 'copyright' => '© Tyndale House Foundation', 'license' => 'copyrighted', 'license_status' => 'approved', 'source_url' => 'https://tyndale.com', 'api_bible_id' => '826f63861180e056-01', 'active' => 1],
    ['code' => 'nbla',    'name' => 'Nueva Biblia de las Américas', 'language' => 'es', 'copyright' => '© The Lockman Foundation',   'license' => 'copyrighted', 'license_status' => 'approved', 'source_url' => 'https://www.lockman.org', 'api_bible_id' => 'ce11b813f9a27e20-01', 'active' => 1],
    // KJV 400th Anniv. — disponible en el plan, pero §9.8 ToS la restringe en
    // UK/territorios británicos y no hay geo-bloqueo en hosting compartido.
    // Además ya hay KJV local (eBible.org). Dejar inactiva.
    ['code' => 'kjv400',  'name' => 'KJV 400th Anniversary Study Edition', 'language' => 'en', 'copyright' => 'Public Domain (texto) / edición de estudio', 'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'api_bible_id' => null, 'active' => 0],
    ['code' => 'lbla',    'name' => 'La Biblia de las Américas',    'language' => 'es', 'copyright' => '© The Lockman Foundation',   'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'active' => 0],
    ['code' => 'dhh',     'name' => 'Dios Habla Hoy',               'language' => 'es', 'copyright' => '© Sociedades Bíblicas Unidas', 'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'active' => 0],
    ['code' => 'tla',     'name' => 'Traducción en Lenguaje Actual','language' => 'es', 'copyright' => '© United Bible Societies',   'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'active' => 0],
    ['code' => 'pdt',     'name' => 'Palabra de Dios para Todos',   'language' => 'es', 'copyright' => '© Centro Mundial de Traducción de la Biblia / Bible League International', 'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'active' => 0],
];
