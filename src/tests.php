<?php
require_once __DIR__ . '/config/config_interface.php';
require_once __DIR__ . '/config/file_config.php';
require_once __DIR__ . '/cache/file_cache.php';

use \sts\config\file_config;
$config = new file_config;
$cache = new \sts\cache\file_cache($config);

// Check if the cache file exists and is not expired

/*$cacheFile = $cache->getFilePath('class_map.php');

if (file_exists($cacheFile) && filemtime($cacheFile) > time() - 3600) {
    // Load the class map from the cache file
    $classMap = require $cacheFile;
} else {
    // Generate the class map and store it in the cache file
    $classMap = generateClassMap();
    $cache->set($cacheFile, $classMap);
}*/
//var_dump($config);
$last_modified = $cache->get('class_map.php', 'last_modified');
var_dump($cache);