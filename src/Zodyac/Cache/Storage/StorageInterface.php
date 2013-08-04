<?php

namespace Zodyac\Cache\Storage;

/**
 * An interface for cache storage engines.
 */
interface StorageInterface
{
    /**
     * Retrieves the cached value at the given key.
     *
     * @param string $key
     * @return Result
     */
    public function get($key);

    /**
     * Retrieves multiple cached values in as few requests as possible.
     *
     * @param array $keys
     * @return array A Result for each key requested
     */
    public function getMulti(array $keys);

    /**
     * Sets the value in the cache at the given key.
     *
     * @param string $key
     * @param mixed $value
     * @param integer $expiration The time in seconds before the cache key is invalidated
     * @return boolean Returns True if the value was set
     */
    public function set($key, $value, $expiration = null);

    /**
     * Adds the value in the cache at the given key.
     *
     * @param string $key
     * @param mixed $value
     * @param integer $expiration The time in seconds before the cache key is invalidated
     * @return boolean Returns True if the value was set, False if it could not be set
     */
    public function add($key, $value, $expiration = null);

    /**
     * Increments the counter at the given key.
     *
     * @param string $key
     * @param integer $value The initial value
     * @param integer $expiration The time in seconds before the cache key is invalidated
     * @return integer The new incremented value (or the initial value if the cache key was not found)
     */
    public function increment($key, $value = 0, $expiration = null);

    /**
     * Deletes the value at the given key.
     *
     * @param string $key
     * @return boolean
     */
    public function delete($key);

    /**
     * Flushes the cache of all data.
     *
     * @return boolean
     */
    public function flush();
}
