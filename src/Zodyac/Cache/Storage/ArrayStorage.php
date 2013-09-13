<?php

namespace Zodyac\Cache\Storage;

use Zodyac\Cache\Result;

/**
 * A cache storage implementation that stores data in an associative array.
 *
 * This should only be used during development/testing when we don't want to keep
 * any cached data for more than a single request.
 */
class ArrayStorage implements StorageInterface
{
    /**
     * The cached data
     *
     * @var array
     */
    protected $data = array();

    public function get($key)
    {
        if (!isset($this->data[$key])) {
            return new Result($key, false);
        }

        return new Result($key, true, $this->data[$key]);
    }

    public function getMulti(array $keys)
    {
        $results = array();
        foreach ($keys as $index => $key) {
            $results[$index] = $this->get($key);
        }

        return $results;
    }

    public function set($key, $value, $expiration = 0)
    {
        $this->data[$key] = $value;

        return true;
    }

    public function add($key, $value, $expiration = 0)
    {
        if (isset($this->data[$key])) {
            return false;
        }

        $this->data[$key] = $value;

        return true;
    }

    public function increment($key, $value = 0, $expiration = 0)
    {
        if (!isset($this->data[$key])) {
            $value = (int) $value;
            $this->data[$key] = $value;

            return $value;
        }

        $this->data[$key]++;

        return $this->data[$key];
    }

    public function delete($key)
    {
        if (!isset($this->data[$key])) {
            return false;
        }

        unset($this->data[$key]);

        return true;
    }

    public function flush()
    {
        $this->data = array();

        return true;
    }
}
