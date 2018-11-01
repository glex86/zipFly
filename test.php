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
$zipFile->create(GTEST_DIR.'onfly'.$nr.'.zip');

$zipFile->addFile(GTEST_DIR."zipFly/zipFly64.php", "zipFly64.php", \zipFly\parts\constants::METHOD_BZIP2);
$zipFile->addFile(GTEST_DIR."zipFly/parts/headers.php", "parts/headers.php", \zipFly\parts\constants::METHOD_DEFLATE);
$zipFile->close();