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
    // En trámite (DBL): se muestran cuando license_status=approved + active=1.
    ['code' => 'rvr1960', 'name' => 'Reina-Valera 1960',            'language' => 'es', 'copyright' => '© 1960 Sociedades Bíblicas en América Latina; © renovado 1988 Sociedades Bíblicas Unidas', 'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'active' => 0],
    ['code' => 'nvi',     'name' => 'Nueva Versión Internacional',  'language' => 'es', 'copyright' => '© Biblica, Inc.',          'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'active' => 0],
    ['code' => 'ntv',     'name' => 'Nueva Traducción Viviente',    'language' => 'es', 'copyright' => '© Tyndale House Foundation', 'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'active' => 0],
    ['code' => 'nbla',    'name' => 'Nueva Biblia de las Américas', 'language' => 'es', 'copyright' => '© The Lockman Foundation',   'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'active' => 0],
    ['code' => 'lbla',    'name' => 'La Biblia de las Américas',    'language' => 'es', 'copyright' => '© The Lockman Foundation',   'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'active' => 0],
    ['code' => 'dhh',     'name' => 'Dios Habla Hoy',               'language' => 'es', 'copyright' => '© Sociedades Bíblicas Unidas', 'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'active' => 0],
    ['code' => 'tla',     'name' => 'Traducción en Lenguaje Actual','language' => 'es', 'copyright' => '© United Bible Societies',   'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'active' => 0],
    ['code' => 'pdt',     'name' => 'Palabra de Dios para Todos',   'language' => 'es', 'copyright' => '© Centro Mundial de Traducción de la Biblia / Bible League International', 'license' => 'copyrighted', 'license_status' => 'requested', 'source_url' => null, 'active' => 0],
];
