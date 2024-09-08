<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/Core/Utils/Autoloader.php';

// Verifică dacă clasa Autoloader există și afișează calea la acest fisier
if(!file_exists(dirname(__DIR__) . '/Core/Utils/Autoloader.php')) {
    var_dump('Autoloader file not found');
    return;
}
use STS\Core\Utils\Autoloader;

$autoloader = new Autoloader(dirname(__DIR__) . '/cache/');
$autoloader->generateClassMap(__DIR__);
$autoloader->register();

var_dump($autoloader);