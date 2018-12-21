<?php

function gAutoLoader($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

    if (is_readable($class . '.php')) {
        include_once $class . '.php';
        return true;
    }

    return false;
}

spl_autoload_register('gAutoLoader');


define('GTEST_DIR', __DIR__.'/');

if (isset($argv[1])) {
    $nr = $argv[1];
} else {
    $nr = '';
}



//$zipFile = new \zipFly\zipFly64(GTEST_DIR.'onfly'.$nr.'.zip');
$zipFile = new \zipFly\zipFly64();
$zipFile->setDebugMode(false);
$zipFile->setZipFeature(true, false);

$zipFile->create(GTEST_DIR.'onfly'.$nr.'.zip');

$zipFile->addFile(GTEST_DIR."zipFly/zipFly64.php", "zipFly64.php", \zipFly\zipFly64::METHOD_BZIP2);
$zipFile->addFile(GTEST_DIR."zipFly/parts/headers.php", "parts/headers.php", \zipFly\zipFly64::METHOD_DEFLATE);
$zipFile->addFromString("Zip64\nTest\n", 'test.txt');

$zipFile->close();