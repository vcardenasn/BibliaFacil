<?php

return function (TestCase $t): void {
    $js = file_get_contents(BASE_PATH . '/public/assets/app.js');
    $css = file_get_contents(BASE_PATH . '/public/assets/app.css');
    $mias = file_get_contents(BASE_PATH . '/app/Views/mias.php');

    $t->run('devocional: acción disponible desde el menú del versículo', function () use ($t, $js) {
        $t->assertTrue(str_contains($js, 'data-a="devotional"'));
        $t->assertTrue(str_contains($js, 'Hacer devocional'));
    });

    $t->run('devocional: presenta las cuatro preguntas de reflexión', function () use ($t, $js) {
        foreach ([
            '¿Qué me enseña hoy la Palabra de Dios acerca de Él, de mí o de cómo debo vivir?',
            '¿Qué está mostrando la Palabra de Dios que necesito reconocer, cambiar o dejar en mi vida?',
            '¿Qué pensamiento, actitud, decisión o conducta necesito corregir a la luz de lo que Dios me enseña?',
            '¿Qué debo hacer hoy para poner en práctica lo que Dios me ha enseñado?',
        ] as $question) {
            $t->assertTrue(str_contains($js, $question), "falta pregunta: {$question}");
        }
    });

    $t->run('devocional: persiste respuestas y permite elegir una porción', function () use ($t, $js) {
        $t->assertTrue(str_contains($js, 'rec.devotional'));
        $t->assertTrue(str_contains($js, 'class="vs-dev-range"'));
        $t->assertTrue(str_contains($js, 'data-a="savedevotional"'));
    });

    $t->run('devocional: tiene estilos y filtro en Mis anotaciones', function () use ($t, $css, $mias) {
        $t->assertTrue(str_contains($css, '.vs-devotional'));
        $t->assertTrue(str_contains($mias, 'data-f="devotional"'));
    });
};
