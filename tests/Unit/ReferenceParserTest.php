<?php

use Biblia\Bible\ReferenceParser;

// US-013 — el parser convierte texto libre en referencia estructurada.

return function (TestCase $t): void {
    $books = require CONFIG_PATH . '/books.php';
    $parser = new ReferenceParser($books);

    $cases = [
        'Juan 3:16'        => ['JHN', 3, 16, null],
        'jn 3.16-18'       => ['JHN', 3, 16, 18],
        'Génesis 1'        => ['GEN', 1, null, null],
        'gen 1:1'          => ['GEN', 1, 1, null],
        'salmos 23'        => ['PSA', 23, null, null],
        'Salmo 91:1-2'     => ['PSA', 91, 1, 2],
        '1 Corintios 13'   => ['1CO', 13, null, null],
        '1cor 13:4'        => ['1CO', 13, 4, null],
        '1 Juan 2:1'       => ['1JN', 2, 1, null],
        '1jn 2'            => ['1JN', 2, null, null],
        'apocalipsis 22'   => ['REV', 22, null, null],
        'Apocalipsis 21:4' => ['REV', 21, 4, null],
        'Cantares 2'       => ['SNG', 2, null, null],
        'romanos 8:28'     => ['ROM', 8, 28, null],
        'Jeremías 29:11'   => ['JER', 29, 11, null],
    ];

    foreach ($cases as $input => [$osis, $ch, $v, $vEnd]) {
        $t->run("parse '{$input}'", function () use ($parser, $input, $osis, $ch, $v, $vEnd) {
            $ref = $parser->parse($input);
            if ($ref === null) {
                throw new RuntimeException('parse() returned null');
            }
            if ($ref['osis'] !== $osis) {
                throw new RuntimeException("osis: got {$ref['osis']}, want {$osis}");
            }
            if ($ref['chapter'] !== $ch) {
                throw new RuntimeException("chapter: got {$ref['chapter']}, want {$ch}");
            }
            if ($ref['verse'] !== $v) {
                throw new RuntimeException("verse: got " . var_export($ref['verse'], true) . ", want " . var_export($v, true));
            }
            if ($ref['verse_end'] !== $vEnd) {
                throw new RuntimeException("verse_end: got " . var_export($ref['verse_end'], true) . ", want " . var_export($vEnd, true));
            }
        });
    }

    $t->run("no parse 'juanita 5'", fn () => $t->assertNull($parser->parse('juanita 5')));
    $t->run("no parse 'xyz 1:1'", fn () => $t->assertNull($parser->parse('xyz 1:1')));
    $t->run("no parse ''", fn () => $t->assertNull($parser->parse('')));
    $t->run("no parse 'san juan 3' (no es alias)", fn () => $t->assertNull($parser->parse('san juan 3')));
    $t->run("'juan' sin capítulo → cap 1", function () use ($parser, $t) {
        $r = $parser->parse('juan');
        $t->assertSame('JHN', $r['osis']);
        $t->assertSame(1, $r['chapter']);
    });
};
