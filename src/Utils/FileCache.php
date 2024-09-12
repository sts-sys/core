<?php
// src/Utils/FileCache.php

namespace sts\core\utils;

class FileCache
{
    protected $cacheFile;
    protected $cacheData = [];

    public function __construct(string $cacheFile)
    {
        $this->cacheFile = $cacheFile;
        $this->loadCache();
    }

    protected function loadCache(): void
    {
        if (file_exists($this->cacheFile)) {
            $this->cacheData = require $this->cacheFile;
        } else {
            // Încearcă să creezi fișierul dacă nu există
            try {
                file_put_contents($this->cacheFile, '<?php return [];');
            } catch (\Exception $e) {
                error_log("Failed to create cache file: " . $e->getMessage());
                throw new \RuntimeException("Cannot create cache file at {$this->cacheFile}");
            }
            $this->cacheData = [];
        }
    }

    public function saveCache(): void
    {
        try {
            file_put_contents($this->cacheFile, '<?php return ' . var_export($this->cacheData, true) . ';');
        } catch (\Exception $e) {
            error_log("Failed to write cache file: " . $e->getMessage());
            throw new \RuntimeException("Cannot write to cache file at {$this->cacheFile}");
        }
    }

    public function get(string $key)
    {
        return $this->cacheData[$key] ?? null;
    }

    public function set(string $key, $value): void
    {
        $this->cacheData[$key] = $value;
    }
}
