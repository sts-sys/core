<?php
namespace sts\cache;

/**
 * Interface driver_interface
 *
 * Defines the basic methods required for a cache driver.
 */
interface driver_interface
{
    /**
     * Retrieve a value from the cache by its key.
     *
     * @param string $key The key of the cache entry.
     * @return mixed The cached value or null if not found.
     */
    public function get(string $key): mixed;

    /**
     * Store a value in the cache.
     *
     * @param string $key The key to store the value under.
     * @param mixed $value The value to store in the cache.
     * @param int $ttl The time-to-live for the cache entry, in seconds.
     * @return void
     */
    public function set(string $key, mixed $value, int $ttl = 3600): void;

    /**
     * Delete a value from the cache by its key.
     *
     * @param string $key The key of the cache entry to delete.
     * @return bool True if the entry was deleted, false otherwise.
     */
    public function delete(string $key): bool;

    /**
     * Clear all entries from the cache.
     *
     * @return bool True if the cache was cleared, false otherwise.
     */
    public function clear(): bool;

    /**
     * Retrieve all entries from the cache.
     *
     * @return array An associative array of all cache entries.
     */
    public function getAll(): array;
}