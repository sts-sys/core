<?php
namespace STS\Core\Utils;

use STS\Core\Events\EventManager;

class CacheManager
{
    protected static ?CacheManager $instance = null;  // Instanța singleton
    protected static array $config = [
        'backend' => 'file',           // Tipul de backend implicit
        'cache_dir' => 'cache',        // Directorul pentru cache
        'default_ttl' => 3600,         // TTL implicit
        'compression' => true,         // Compresie activată
        'max_size' => 10485760,        // Dimensiunea maximă a cache-ului: 10 MB
    ];

    protected $backend;               // Backend-ul de cache (File, Redis, etc.)
    protected $eventManager;          // Managerul de evenimente

    /**
     * Constructor privat pentru a preveni instanțierea directă.
     */
    private function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager; // Asociază managerul de evenimente

        // Selectează backend-ul pe baza configurației
        switch (self::$config['backend']) {
            case 'redis':
                $this->backend = new RedisCache(self::$config); // O implementare de cache pe Redis
                break;
            case 'memcached':
                $this->backend = new MemcachedCache(self::$config); // O implementare de cache pe Memcached
                break;
            case 'file':
            default:
                $this->backend = new FileCache(storage_path(self::$config['cache_dir']));
                break;
        }
    }

    /**
     * Obține instanța singleton a clasei CacheManager.
     *
     * @return CacheManager
     */
    public static function getInstance(): CacheManager
    {
        if (self::$instance === null) {
            $eventManager = EventManager::getInstance(); // Presupunem că ai un singleton pentru EventManager
            self::$instance = new self($eventManager);
        }
        return self::$instance;
    }

    /**
     * Configurează setările cache-ului.
     *
     * @param array $config
     * @return void
     */
    public static function configure(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Setează o valoare în cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl Timpul de expirare
     * @return void
     */
    public function set(string $key, $value, int $ttl = null): void
    {
        $this->eventManager->dispatch('cache.beforeSet', ['key' => $key, 'value' => $value, 'ttl' => $ttl]);

        if ($this->isCacheFull()) {
            $this->evictEntries();
        }

        $this->backend->set($key, $value, $ttl ?? self::$config['default_ttl']);

        $this->eventManager->dispatch('cache.afterSet', ['key' => $key, 'value' => $value, 'ttl' => $ttl]);
    }

    /**
     * Obține o valoare din cache.
     *
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key)
    {
        $this->eventManager->dispatch('cache.beforeGet', ['key' => $key]);

        $data = $this->backend->get($key);

        $this->eventManager->dispatch('cache.afterGet', ['key' => $key, 'data' => $data]);

        return $data;
    }

    /**
     * Șterge o valoare din cache.
     *
     * @param string $key
     * @return void
     */
    public function delete(string $key): void
    {
        $this->backend->delete($key);
        $this->eventManager->dispatch('cache.delete', ['key' => $key]);
    }

    /**
     * Curăță toate datele din cache.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->backend->clear();
        $this->eventManager->dispatch('cache.clear');
    }

    /**
     * Verifică dacă cache-ul a depășit limita de dimensiune.
     *
     * @return bool
     */
    protected function isCacheFull(): bool
    {
        return $this->backend->getCacheSize() > self::$config['max_size'];
    }

    /**
     * Evacuează intrările din cache bazate pe politica de evacuare.
     *
     * @return void
     */
    protected function evictEntries(): void
    {
        switch (self::$config['eviction_policy']) {
            case 'LRU':
                $this->backend->evictLRU();  // Implementare LRU (Least Recently Used)
                break;
            case 'LFU':
                $this->backend->evictLFU();  // Implementare LFU (Least Frequently Used)
                break;
            default:
                // Politică de evacuare implicită
                break;
        }
    }

    /**
     * Obține statistici despre starea cache-ului.
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        return [
            'total_size' => $this->backend->getCacheSize(),
            'entry_count' => $this->backend->getEntryCount(),
            'hit_rate' => $this->backend->getHitRate(),
            'miss_rate' => 100 - $this->backend->getHitRate(),
        ];
    }
}
