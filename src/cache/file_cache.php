<?php
namespace sts\cache;

use RuntimeException;
use sts\config\file_config;

/**
 * Class file_cache
 *
 * @package sts\cache\file_cache
 * 
 */
class file_cache
{
    protected string $cachePath;
    protected array $cacheData = [];
    protected bool $cacheLoaded = false;
    protected int $cacheLastUpdated = 0;
    
    /**
     * Constructor.
     * Initializează calea de cache și încarcă datele din cache.
     */
    public function __construct(\sts\config\file_config &$config)
    {
        $this->cachePath = rtrim($config->get('cache', 'stores.file.cache.path'), '/') . '/' ?? '/cache';
        $this->loadCache();
    }
    
    /**
     * Încarcă toate datele din cache existente.
     */
    protected function loadCache(): void
    {
        if (!is_dir($this->cachePath))
            throw new RuntimeException(sprintf('Cache directory `%s` does not exist.', $this->cachePath) );

        foreach (glob($this->cachePath . '*.cache') as $file) {
            if ($this->validateCacheFile($file)) {
                $content = unserialize(file_get_contents($file));

                $this->cacheLastUpdated = max($this->cacheLastUpdated, filemtime($file));

                if ($this->isExpired($content)) {
                    $this->deleteFile($file); // Șterge intrările expirate
                } else {
                    $this->cacheData[basename($file)] = $content;
                }
            }
        }

        $this->cacheLoaded = true;
    }

    /**
     * Verifică dacă fișierul de cache este valid.
     */
    protected function validateCacheFile(string $file): bool
    {
        return file_exists($file) && is_readable($file);
    }

    /**
     * Setează numele fișierului de cache.
     */
    protected function setCacheFile(string $file): string
    {
        return $this->cachePath . md5($file) . '.cache';
    }

    /**
     * Returnează valoarea din cache pentru o cheie dată.
     */
    public function get(string $fileName, string $key = ''): string
    {
        $filePath = $this->setCacheFile($fileName);
        if ($this->validateCacheFile($filePath)) {
            $content = unserialize(file_get_contents($filePath));
            
            if ($this->isExpired($content)) {
                $this->deleteFile($filePath); // Șterge fișierul dacă este expirat
                return '';
            }
            return $content[$key] ?? '';
        }

        return '';
    }

    /**
     * Setează o valoare în cache pentru o cheie dată.
     */
    public function set(string $fileName, string $key, string|array $value, int $ttl = 3600): void
    {
        $filePath = $this->setCacheFile($fileName);
        $data = ['value' => $value, 'expires_at' => time() + $ttl];

        // Șterge fișierele expirate înainte de a scrie
        if (file_exists($filePath)) {
            $content = unserialize(file_get_contents($filePath));

            if ($this->isExpired($content)) {
                $this->deleteFile($filePath);
            }
        }

        file_put_contents($filePath, serialize($data));
    }

    /**
     * Șterge o intrare din cache.
     */
    public function delete(string $fileName): bool
    {
        $filePath = $this->setCacheFile($fileName);
        if ($this->validateCacheFile($filePath)) {
            unlink($filePath);
            return true;
        }
        return false;
    }

    /**
     * Salvează toate datele în fișierul de cache.
     */
    public function save(): bool
    {
        foreach ($this->cacheData as $file => $data) {
            $filePath = $this->setCacheFile($file);
            $serializedData = serialize($data);
            file_put_contents($filePath, $serializedData);
        }
        return true;
    }

    /**
     * Golește toate intrările din cache.
     */
    public function clear(): bool
    {
        $files = glob($this->cachePath . '*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $this->cacheData = [];
        return true;
    }

    /**
     * Returnează toate intrările din cache.
     */
    public function get_all(): array
    {
        return $this->cacheData;
    }

        /**
     * Verifică dacă o intrare de cache este expirată.
     */
    protected function isExpired(array $data): bool
    {
        return isset($data['expires_at']) && $data['expires_at'] < time();
    }

    /**
     * Șterge fișierul de cache.
     */
    protected function deleteFile(string $file): void
    {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}