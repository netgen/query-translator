<?php

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    throw new RuntimeException('Install dependencies using composer to run the test suite.');
}

require_once $autoload;
