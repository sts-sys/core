<?php
require_once __DIR__ . '/Utils/FileCache.php';
require_once __DIR__ . '/Utils/Autoloader.php';
require_once __DIR__ . '/fallback_autoload.php';

use STS\Core\Utils\Autoloader;

$autoloader = new Autoloader(dirname(__DIR__) . '/cache/');
$autoloader->generateClassMap(__DIR__);

$autoloader->autoloadFiles([
    __DIR__ . '/Helpers/globals.php'
]);

/*$autoloader->autoloadNamespaces([
    'App\\' => __DIR__. '/app/',
    'Core\\' => __DIR__. '/src/Core/',
    'Database\\' => __DIR__. '/src/Database/',
    'Tests\\' => __DIR__. '/tests/',
]);*/

// Register the autoloader after registering the namespaces and files
$autoloader->register();

var_dump($autoloader);