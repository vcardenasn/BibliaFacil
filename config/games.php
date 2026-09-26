<?php

/**
 * Catálogo de juegos bíblicos (EPIC 17).
 * slug => [nombre, emoji, descripción, ready]
 * 'ready' controla si la tarjeta enlaza o muestra "Muy pronto".
 */
return [
    'vf'        => ['name' => '¿Verdadero o Falso?',    'emoji' => '🤔', 'desc' => '¿Será verdad? ¡Rápido, decide!', 'ready' => true],
    'trivia'    => ['name' => 'Trivia Bíblica',         'emoji' => '🏆', 'desc' => 'Preguntas de personajes, milagros y más.', 'ready' => true],
    'versiculo' => ['name' => 'Completa el Versículo',  'emoji' => '📖', 'desc' => '¿Qué palabra falta en la Biblia?', 'ready' => false],
    'historia'  => ['name' => 'Ordena la Historia',     'emoji' => '🎬', 'desc' => 'Pon las escenas en orden.', 'ready' => true],
    'memory'    => ['name' => 'Memory Bíblico',         'emoji' => '🃏', 'desc' => 'Encuentra todas las parejas.', 'ready' => true],
    'libros'    => ['name' => 'Ordena los Libros',      'emoji' => '📚', 'desc' => '¿Sabes el orden de la Biblia?', 'ready' => true],
    'personaje' => ['name' => 'Adivina el Personaje',   'emoji' => '🔍', 'desc' => 'Descubre quién es con pistas.', 'ready' => false],
];
