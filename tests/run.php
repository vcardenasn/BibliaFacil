<?php

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/TestCase.php';

$test = new TestCase();
$files = glob(__DIR__ . '/Unit/*Test.php');

sort($files);
foreach ($files as $file) {
    $suite = require $file;
    $suite($test);
}
exit($test->finish());
