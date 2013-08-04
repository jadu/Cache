<?php

namespace Zodyac\Cache\Storage;

use Zodyac\Cache\Exception\RuntimeException;
use Zodyac\Cache\Result;

/**
 * A cache storage implementation that stores data in APC user cache.
 *
 * @see http://www.php.net/manual/en/ref.apc.php
 */
class ApcStorage implements StorageInterface
{
    public function get($key)
    {
        $value = apc_fetch($key, $success);
        if ($success) {
            return new Result($key, true, $value);
        }

        return new Result($key, false);
    }

    public function getMulti(array $keys)
    {
        $results = array();
        foreach ($keys as $index => $key) {
            $results[$index] = $this->get($key);
        }

        return $results;
    }

    public function set($key, $value, $expiration = null)
    {
        return apc_store($key, $value, $expiration);
    }

    public function add($key, $value, $expiration = null)
    {
        return apc_add($key, $value, $expiration);
    }

    public function increment($key, $value = 0, $expiration = null)
    {
        $value = (int) $value;

        if (!apc_add($key, $value, $expiration)) {
            $newValue = apc_inc($key, 1, $success);
            if ($success) {
                // Only update the return value on success, otherwise the initial value is returned
                $value = $newValue;
            }
        }

        return $value;
    }

    public function delete($key)
    {
        return apc_delete($key);
    }

    public function flush()
    {
        // Only flush the user cache, not byte-code.
        return apc_clear_cache('user');
    }
}
