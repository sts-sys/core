<?php
// src/Core/Autoloader.php
namespace STS\Core;

use STS\Core\Utils\FileCache;
use STS\Core\Utils\LazyLoader;

class Autoloader
{
    protected $classMap = [];
    protected $namespaceMap = [];
    protected $cache;
    protected $lastCheckedTime;

    public function __construct(string $cacheFile)
    {
        $this->cache = new FileCache($cacheFile);
        $this->classMap = $this->cache->get('class_map') ?? [];
        $this->lastCheckedTime = $this->cache->get('last_checked_time') ?? time();
    }

    // Înregistrează autoloader-ul
    public function register(): void
    {
        spl_autoload_register([$this, 'autoload']);
    }

    // Adaugă o clasă în harta de mapare
    public function addClassMap(string $class, string $file): void
    {
        $this->classMap[$class] = $file;
    }

    // Adaugă un namespace în maparea namespace-urilor
    public function addNamespaceMap(string $namespace, string $baseDir): void
    {
        $this->namespaceMap[$namespace] = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    // Încărca o clasă
    public function autoload(string $class): void
    {
        $this->checkForNewFiles();

        if (isset($this->classMap[$class])) {
            $this->requireFile($this->classMap[$class]);
            return;
        }

        foreach ($this->namespaceMap as $prefix => $dir) {
            if (strpos($class, $prefix) === 0) {
                $relativeClass = substr($class, strlen($prefix));
                $file = $dir . str_replace('\\', '/', $relativeClass) . '.php';

                if ($this->requireFile($file)) {
                    $this->addClassMap($class, $file);
                    $this->cache->set('class_map', $this->classMap);
                    $this->cache->saveCache();
                    return;
                }
            }
        }

        error_log("Class $class could not be loaded.");
    }

    // Încarcă un fișier PHP
    protected function requireFile(string $file): bool
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }

    // Preîncărca clasele frecvent utilizate
    public function preloadClasses(array $classes): void
    {
        foreach ($classes as $class) {
            if (!class_exists($class, false)) {
                $this->autoload($class);
            }
        }
    }

    // Verifică dacă există fișiere noi sau modificări în directoare
    protected function checkForNewFiles(): void
    {
        $currentTime = time();
        // Verificăm dacă a trecut un interval de timp specificat (ex. 5 minute) de la ultima verificare
        if ($currentTime - $this->lastCheckedTime < 300) { // 300 secunde (5 minute)
            return;
        }

        foreach ($this->namespaceMap as $namespace => $directory) {
            $this->scanDirectoryForChanges($directory);
        }

        $this->lastCheckedTime = $currentTime;
        $this->cache->set('last_checked_time', $this->lastCheckedTime);
        $this->cache->saveCache();
    }

    // Scanează un director pentru a detecta modificări sau fișiere noi
    protected function scanDirectoryForChanges(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $this->getClassNameFromFile($file->getPathname());
                if ($className && !isset($this->classMap[$className])) {
                    // Fișier nou detectat, îl adăugăm la harta de clase
                    $this->classMap[$className] = $file->getPathname();
                    error_log("New class detected: $className in file {$file->getPathname()}");
                }
            }
        }
    }

    // Obține numele clasei dintr-un fișier PHP
    protected function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        if (preg_match('/namespace\s+(.+?);/', $content, $matchesNamespace) &&
            preg_match('/class\s+(\w+)/', $content, $matchesClass)) {
            return $matchesNamespace[1] . '\\' . $matchesClass[1];
        }
        return null;
    }
}
