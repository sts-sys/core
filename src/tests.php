<?php
if(!file_exists(dirname(__DIR__) . '/vendor/autoload.php')) 
    echo '* Please run "composer install" to install dependencies first.' . PHP_EOL;
    return;

require_once dirname(__DIR__) . '/vendor/autoload.php';

define('APP_PATH', __DIR__);
define('STS_CONFIG_LOAD', dirname(__DIR__) . '/config/');