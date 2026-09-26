<?php

// EPIC 19 / US-190 — colecciones temáticas indexables.
// refs: [OSIS, capítulo, versículo] — el texto sale de la BD por versión.
return [
    'amor' => [
        'name' => 'Amor', 'emoji' => '❤️',
        'desc' => 'Versículos sobre el amor de Dios y el amor al prójimo.',
        'intro' => 'La Biblia enseña que Dios es amor. Estos versículos hablan de su amor por nosotros y de cómo amar a los demás.',
        'refs' => [['JHN',3,16],['1CO',13,4],['1CO',13,13],['1JN',4,8],['1JN',4,19],['ROM',5,8],['JHN',15,13],['SNG',8,7],['PRO',10,12],['1PE',4,8],['MAT',22,37],['DEU',6,5],['JER',31,3],['ROM',13,10],['EPH',5,2]],
    ],
    'fe' => [
        'name' => 'Fe', 'emoji' => '🙏',
        'desc' => 'Versículos para fortalecer tu fe en Dios.',
        'intro' => 'La fe es confiar en Dios aun sin ver. Estos pasajes muestran qué es la fe y cómo vivirla cada día.',
        'refs' => [['HEB',11,1],['HEB',11,6],['ROM',10,17],['2CO',5,7],['MAT',17,20],['MRK',9,23],['EPH',2,8],['JAS',2,17],['GAL',2,20],['PRO',3,5],['MAT',21,22],['HEB',12,2],['1JN',5,4],['MAT',9,22]],
    ],
    'animo' => [
        'name' => 'Ánimo y consuelo', 'emoji' => '🕊️',
        'desc' => 'Versículos de ánimo para tiempos difíciles.',
        'intro' => 'Cuando el corazón está cansado, la Palabra trae consuelo. Estos versículos son para los días duros.',
        'refs' => [['JOS',1,9],['ISA',41,10],['PSA',23,4],['PSA',46,1],['MAT',11,28],['JHN',14,27],['JHN',16,33],['ROM',8,28],['2CO',1,4],['PHP',4,6],['PHP',4,13],['DEU',31,6],['PSA',34,18],['REV',21,4],['1PE',5,7]],
    ],
    'paz' => [
        'name' => 'Paz', 'emoji' => '☮️',
        'desc' => 'Versículos sobre la paz que solo Dios da.',
        'intro' => 'Jesús prometió una paz diferente a la del mundo. Estos textos hablan de esa paz que guarda el corazón.',
        'refs' => [['JHN',14,27],['PHP',4,7],['ISA',26,3],['COL',3,15],['ROM',5,1],['PSA',4,8],['2TH',3,16],['NUM',6,24],['JHN',16,33],['MAT',5,9],['ROM',12,18],['PSA',29,11]],
    ],
    'esperanza' => [
        'name' => 'Esperanza', 'emoji' => '🌅',
        'desc' => 'Versículos de esperanza para mirar adelante con confianza.',
        'intro' => 'Dios tiene planes de bien para su pueblo. Estos versículos sostienen la esperanza cuando todo parece oscuro.',
        'refs' => [['JER',29,11],['ROM',15,13],['ROM',5,5],['HEB',6,19],['LAM',3,22],['LAM',3,24],['PSA',42,11],['1PE',1,3],['TIT',2,13],['ISA',40,31],['ROM',8,25],['PSA',130,5],['MIC',7,7]],
    ],
    'familia' => [
        'name' => 'Familia', 'emoji' => '👨‍👩‍👧‍👦',
        'desc' => 'Versículos sobre la familia, los padres y los hijos.',
        'intro' => 'La familia es el primer lugar donde aprendemos de Dios. Estos pasajes guían el hogar.',
        'refs' => [['PRO',22,6],['JOS',24,15],['EPH',6,1],['EPH',6,4],['COL',3,20],['PSA',127,3],['PRO',17,6],['1TI',5,8],['EXO',20,12],['PRO',31,28],['GEN',2,24],['MRK',10,9],['PSA',133,1]],
    ],
    'perdon' => [
        'name' => 'Perdón', 'emoji' => '🤝',
        'desc' => 'Versículos sobre el perdón de Dios y perdonar a otros.',
        'intro' => 'Dios perdona por completo y nos llama a perdonar como fuimos perdonados. Estos textos lo muestran.',
        'refs' => [['1JN',1,9],['EPH',4,32],['MAT',6,14],['COL',3,13],['LUK',6,37],['LUK',23,34],['PSA',103,12],['ISA',1,18],['ISA',43,25],['MAT',18,22],['PRO',17,9],['MIC',7,18]],
    ],
    'sabiduria' => [
        'name' => 'Sabiduría', 'emoji' => '📚',
        'desc' => 'Versículos sobre pedir y vivir con sabiduría.',
        'intro' => 'La sabiduría empieza con el respeto a Dios. Proverbios y Santiago enseñan a pedirla y practicarla.',
        'refs' => [['PRO',2,6],['PRO',3,13],['PRO',4,7],['PRO',9,10],['JAS',1,5],['JAS',3,17],['COL',3,16],['PRO',15,33],['PRO',19,20],['PSA',90,12],['DAN',2,21],['PRO',16,16]],
    ],
    'oracion' => [
        'name' => 'Oración', 'emoji' => '🛐',
        'desc' => 'Versículos que enseñan a orar y a confiar en la respuesta de Dios.',
        'intro' => 'Orar es hablar con Dios en todo momento. Estos versículos enseñan cómo y por qué orar.',
        'refs' => [['PHP',4,6],['1TH',5,17],['MAT',6,6],['MAT',7,7],['JER',33,3],['JAS',5,16],['MRK',11,24],['PSA',145,18],['ROM',8,26],['1JN',5,14],['LUK',11,9],['PSA',34,17]],
    ],
    'gratitud' => [
        'name' => 'Gratitud', 'emoji' => '🌻',
        'desc' => 'Versículos para dar gracias a Dios en todo.',
        'intro' => 'Dar gracias cambia la mirada del corazón. Estos pasajes invitan a la gratitud diaria.',
        'refs' => [['1TH',5,18],['PSA',100,4],['PSA',107,1],['COL',3,17],['EPH',5,20],['JAS',1,17],['PSA',118,24],['PSA',136,1],['2CO',9,15],['1CH',16,34],['PSA',103,2],['LUK',17,15]],
    ],
    'proteccion' => [
        'name' => 'Protección', 'emoji' => '🛡️',
        'desc' => 'Versículos sobre el cuidado y la protección de Dios.',
        'intro' => 'Dios es refugio en medio del peligro. Estos salmos y promesas hablan de su protección.',
        'refs' => [['PSA',91,1],['PSA',91,11],['PSA',121,7],['PSA',121,8],['PSA',46,1],['ISA',41,10],['PRO',18,10],['2TH',3,3],['DEU',31,6],['NAH',1,7],['PSA',34,7],['2SA',22,31],['EXO',14,14]],
    ],
    'ninos' => [
        'name' => 'Niños', 'emoji' => '🧒',
        'desc' => 'Versículos para niños y sobre el valor de los niños.',
        'intro' => 'Jesús amó a los niños y nos enseñó a tener un corazón como el de ellos. Perfectos para leer en familia.',
        'refs' => [['MAT',19,14],['MRK',10,14],['PRO',22,6],['PSA',127,3],['EPH',6,1],['PRO',20,11],['3JN',1,4],['MAT',18,3],['ISA',54,13],['2TI',3,15],['EXO',20,12],['PSA',8,2]],
    ],
    'fortaleza' => [
        'name' => 'Fortaleza', 'emoji' => '💪',
        'desc' => 'Versículos de fuerza para no rendirse.',
        'intro' => 'La fuerza de Dios se muestra en nuestra debilidad. Estos versículos animan a seguir adelante.',
        'refs' => [['PHP',4,13],['ISA',40,31],['ISA',41,10],['JOS',1,9],['PSA',28,7],['PSA',46,1],['2CO',12,9],['EPH',6,10],['NEH',8,10],['1CO',16,13],['HAB',3,19],['PSA',73,26]],
    ],
    'trabajo' => [
        'name' => 'Trabajo', 'emoji' => '🛠️',
        'desc' => 'Versículos sobre trabajar con propósito y honestidad.',
        'intro' => 'El trabajo hecho para el Señor tiene valor eterno. Estos pasajes guían la vida laboral.',
        'refs' => [['COL',3,23],['PRO',16,3],['PRO',14,23],['ECC',9,10],['PRO',6,6],['2TH',3,10],['1CO',10,31],['PSA',90,17],['GEN',2,15],['PRO',12,11],['EPH',4,28]],
    ],
];
