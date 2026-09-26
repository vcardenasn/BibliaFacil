<?php

// EPIC 05 / US-040 — planes de lectura semilla.
// 'books': 'all' | 'nt' | 'at' | [slugs]. Los días se calculan en ReadingPlan
// repartiendo los capítulos elegidos en partes iguales.

return [
    'biblia-en-un-ano' => [
        'name' => 'La Biblia en un año',
        'emoji' => '📖',
        'desc' => 'De Génesis a Apocalipsis en 365 días: unos 3 capítulos al día.',
        'intro' => 'Leer toda la Biblia parece una montaña, pero a paso constante se recorre. Cada día trae pocos capítulos consecutivos; marca lo leído y retoma donde quedaste.',
        'days' => 365,
        'books' => 'all',
    ],
    'nuevo-testamento-90' => [
        'name' => 'Nuevo Testamento en 90 días',
        'emoji' => '✝️',
        'desc' => 'Los 27 libros del Nuevo Testamento en tres meses.',
        'intro' => 'Tres meses para recorrer desde Mateo hasta Apocalipsis. Un buen plan si quieres conocer a fondo la vida de Jesús y las cartas de la iglesia primitiva.',
        'days' => 90,
        'books' => 'nt',
    ],
    'salmos-proverbios' => [
        'name' => 'Salmos y Proverbios en un mes',
        'emoji' => '🕊️',
        'desc' => 'Los 150 Salmos y los 31 Proverbios repartidos en 31 días.',
        'intro' => 'Alabanza y sabiduría para cada día del mes: varios Salmos y un capítulo de Proverbios por jornada. Ideal como plan devocional mensual.',
        'days' => 31,
        'books' => ['salmos', 'proverbios'],
    ],
];
