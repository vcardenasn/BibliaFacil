<?php

/**
 * Catálogo de juegos bíblicos (EPIC 17).
 * slug => [nombre, imagen, descripción, ready, color]
 * 'ready' controla si la tarjeta enlaza o muestra "Muy pronto".
 * 'img'   = codepoint OpenMoji (CC BY-SA) en public/assets/omoji/{code}.svg.
 * 'color' = identidad visual del juego (--gc en el hub).
 */
return [
    'vf'        => ['name' => '¿Verdadero o Falso?',    'img' => '1F914', 'desc' => '¿Será verdad? ¡Rápido, decide!', 'ready' => true, 'color' => '#2f9e44'],
    'paloma'    => ['name' => 'La Paloma de Noé',         'img' => '1F54A', 'desc' => 'Vuela entre la tormenta y halla la rama de olivo.', 'ready' => true, 'color' => '#1c7ed6'],
    'trivia'    => ['name' => 'Trivia Bíblica',         'img' => '1F9E0', 'desc' => 'Preguntas de personajes, milagros y más.', 'ready' => true, 'color' => '#7450f0'],
    'versiculo' => ['name' => 'Completa el Versículo',  'img' => '1F4D6', 'desc' => 'Versículos por niveles: ¡desbloquéalos todos!', 'ready' => true, 'color' => '#1971c2'],
    'historia'  => ['name' => 'Ordena la Historia',     'img' => '1F4DC', 'desc' => 'Pon las escenas en orden.', 'ready' => true, 'color' => '#f76707'],
    'memory'    => ['name' => 'Memory Bíblico',         'img' => '1F0CF', 'desc' => 'Encuentra todas las parejas.', 'ready' => true, 'color' => '#d6336c'],
    'libros'    => ['name' => 'Ordena los Libros',      'img' => '1F4DA', 'desc' => '¿Sabes el orden de la Biblia?', 'ready' => true, 'color' => '#0ca678'],
    'personaje' => ['name' => 'Adivina el Personaje',   'img' => '1F3AD', 'desc' => 'Descubre quién es con pistas.', 'ready' => true, 'color' => '#e8890c'],
    'sopa'      => ['name' => 'Sopa de Letras',          'img' => '1F50D', 'desc' => 'Encuentra las palabras escondidas.', 'ready' => true, 'color' => '#ae3ec9'],
    'crucigrama'=> ['name' => 'Crucigrama Bíblico',      'img' => '1F9E9', 'desc' => 'Une las palabras con sus pistas.', 'ready' => true, 'color' => '#4263eb'],
];
