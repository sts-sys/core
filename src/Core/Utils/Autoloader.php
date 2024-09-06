<?php
// src/Core/Autoloader.php
namespace STS\Core\Utils;

use STS\Core\Utils\FileCache;

class Autoloader
{
    protected $classMap = [];
    protected $namespaceMap = [];
    protected $cache;

    public function __construct(string $cacheFile)
    {
        $this->cache = new FileCache($cacheFile);
        $this->classMap = $this->cache->get('class_map') ?? [];
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
        $startTime = microtime(true);

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

                    $endTime = microtime(true);
                    error_log("Class $class loaded in " . ($endTime - $startTime) . " seconds.");
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

    // Generarea hărții statice a claselor
    public function generateClassMap(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $this->getClassNameFromFile($file->getPathname());
                if ($className) {
                    $this->classMap[$className] = $file->getPathname();
                }
            }
        }
        $this->cache->set('class_map', $this->classMap);
        $this->cache->saveCache();
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
