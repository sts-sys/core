<?php
declare(strict_types=1);
require_once __DIR__ . '/src/Core/Utils/Autoloader.php';

// Inițializare autoloader și generare hartă
$autoloader = new Autoloader(__DIR__ . '/vendor/cache/autoload_cache.php');
$autoloader->generateClassMap(__DIR__ . '/src');
$autoloader->register();

function sts_fallback_autoload($className)
{
    // Verifică în pluginuri sau API-uri externe pentru clase nedefinite
    if (sts_plugin_class_loader($className) || sts_api_class_loader($className)) {
        return;
    }
    error_log("Class $className could not be found by the primary autoloader.");
}

function sts_plugin_class_loader($className): bool
{
    // Logica de căutare a claselor în pluginuri
    return false;
}

function sts_api_class_loader($className): bool
{
    // Logica de încărcare a claselor dintr-un API extern
    return false;
}

// Înregistrează fallback-ul ca autoloader de urgență
spl_autoload_register('sts_fallback_autoload', true, true);
