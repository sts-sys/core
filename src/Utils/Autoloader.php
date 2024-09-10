<?php
// src/Core/Autoloader.php
declare(strict_types=1);

namespace STS\Core\Utils;
require_once dirname(__DIR__) . '/Utils/FileCache.php';

use STS\Core\Utils\FileCache;

final class Autoloader {
    protected array $classMap = [];
    protected array $namespaceMap = [];
    protected array $autoloadFiles = [];
    protected ?FileCache $cache;
    protected int $lastCheckedTime;

    public function __construct(string $cacheDir)
    {
        $this->checkCacheDirectory($cacheDir);
        $this->cache = new FileCache($cacheDir . '/autoload_cache.php');
        $this->classMap = $this->cache->get('class_map') ?? [];
        $this->lastCheckedTime = $this->cache->get('last_checked_time') ?? time();

    }

    public function register(): void
    {
        spl_autoload_register([$this, 'autoload']);
        foreach ($this->autoloadFiles as $file) {
            require_once $file;
        }
    }

    public function addClassMap(string $class, string $file): void
    {
        $this->classMap[$class] = $file;
    }

    public function addNamespaceMap(string $namespace, string $baseDir): void
    {
        $this->namespaceMap[$namespace] = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function autoloadNamespaces(array $namespaces): void
    {
        foreach ($namespaces as $namespace => $directory) {
            $this->addNamespaceMap($namespace, $directory);
        }
    }

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

        error_log("Class $class could not be loaded.", 0, "/cache/error.log");
    }

    protected function requireFile(string $file): bool
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }

    public function preloadClasses(array $classes): void
    {
        foreach ($classes as $class) {
            if (!class_exists($class, false)) {
                $this->autoload($class);
            }
        }
    }

    protected function checkForNewFiles(): void
    {
        $currentTime = time();
        if ($currentTime - $this->lastCheckedTime < 300) { 
            return;
        }

        foreach ($this->namespaceMap as $namespace => $directory) {
            $this->scanDirectoryForChanges($directory);
        }

        $this->lastCheckedTime = $currentTime;
        $this->cache->set('last_checked_time', $this->lastCheckedTime);
        $this->cache->saveCache();
    }

    protected function scanDirectoryForChanges(string $directory): void
    {
        // Verifică dacă directorul există înainte de a crea iteratorul
        if (!is_dir($directory)) {
            error_log("Directory not found: $directory", 0, "/cache/error.log", true);
            return;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $this->getClassNameFromFile($file->getPathname());
                if ($className && !isset($this->classMap[$className])) {
                    // Fișier nou detectat, îl adăugăm la harta de clase
                    $this->classMap[$className] = $file->getPathname();
                    error_log("New class detected: $className in file {$file->getPathname()}", 0, "/cache/error.log", true);
                }
            }
        }
    }

    /**
     * Utilizează regular expressions pentru a determina numele clasei din un fisiere
     * 
     * @param string $filePath Calea către fisierul PHP
     * @return void 
     */
    public function autoloadFiles(array $filePaths): void
    {
        foreach ($filePaths as $filePath) {
            if (file_exists($filePath)) {
                $this->autoloadFiles = $filePaths;
            } else {
                throw new \RuntimeException(sprintf('The file "%s" could not be found.', $filePath));
            }
        }
    }

    // Definirea metodei generateClassMap()
    public function generateClassMap(string $directory): void
    {
        // Verifică dacă directorul există înainte de a încerca să creezi iteratorul
        if (!is_dir($directory)) {
            error_log("Directory not found: $directory", 0, "/cache/error.log", true);
            return;
        }
    
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

    protected function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        if (preg_match('/namespace\s+(.+?);/', $content, $matchesNamespace) &&
            preg_match('/class\s+(\w+)/', $content, $matchesClass)) {
            return $matchesNamespace[1] . '\\' . $matchesClass[1];
        }
        return null;
    }
    
    // Definirea metodei checkCacheDirectory()
    protected function checkCacheDirectory(string $directory): void
    {
        $cacheDir = $directory;
        $cacheFile = $cacheDir . 'autoload_cache.php';

        // Verifică dacă directorul de cache există, dacă nu, încearcă să-l creezi
        if (!is_dir($cacheDir)) {
            if (!mkdir($cacheDir, 0777, true) && !is_dir($cacheDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $cacheDir));
            }
        }

        // Verifică dacă fișierul de cache există, dacă nu, încearcă să-l creezi
        if (!file_exists($cacheFile)) {
            if (file_put_contents($cacheFile, '<?php return [];') === false) {
                throw new \RuntimeException(sprintf('Failed to create cache file "%s"', $cacheFile));
            }
        }
    }
}