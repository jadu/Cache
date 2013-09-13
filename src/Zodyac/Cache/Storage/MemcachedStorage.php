<?php

namespace Zodyac\Cache\Storage;

use Zodyac\Cache\Exception\RuntimeException;
use Zodyac\Cache\Result;

/**
 * A cache storage implementation that stores data in Memcached.
 *
 * @see http://memcached.org/
 */
class MemcachedStorage implements StorageInterface
{
    protected $resource;

    public function __construct(\Memcached $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Returns the Memcached resource
     *
     * @return Memcached
     */
    public function getMemcachedResource()
    {
        return $this->resource;
    }

    public function get($key)
    {
        $memcached = $this->getMemcachedResource();
        $value = $memcached->get($key);

        $resultCode = $memcached->getResultCode();
        if ($resultCode === \Memcached::RES_SUCCESS) {
            return new Result($key, true, $value);
        }

        if ($resultCode === \Memcached::RES_NOTFOUND) {
            return new Result($key, false);
        }

        throw $this->getExceptionByResultCode($resultCode);
    }

    public function getMulti(array $keys)
    {
        $memcached = $this->getMemcachedResource();
        $values = $memcached->getMulti($keys);
        if ($values === false) {
            throw $this->getExceptionByResultCode($memcached->getResultCode());
        }

        $results = array();
        foreach ($keys as $index => $key) {
            if (isset($values[$key])) {
                $results[$index] = new Result($key, true, $values[$key]);
            } else {
                $results[$index] = new Result($key, false);
            }
        }

        return $results;
    }

    public function set($key, $value, $expiration = 0)
    {
        $memcached = $this->getMemcachedResource();
        if (!$memcached->set($key, $value, $expiration)) {
            throw $this->getExceptionByResultCode($memcached->getResultCode());
        }

        return true;
    }

    public function add($key, $value, $expiration = 0)
    {
        $memcached = $this->getMemcachedResource();
        if (!$memcached->add($key, $value, $expiration)) {
            if ($memcached->getResultCode() === \Memcached::RES_NOTSTORED) {
                return false;
            }

            throw $this->getExceptionByResultCode($memcached->getResultCode());
        }

        return true;
    }

    public function increment($key, $value = 0, $expiration = 0)
    {
        $memcached = $this->getMemcachedResource();
        $value = (int) $value;

        $newValue = $memcached->increment($key, 1);
        if ($newValue === false) {
            $resultCode = $memcached->getResultCode();
            if ($resultCode === \Memcached::RES_NOTFOUND) {
                $newValue = $value;
                $memcached->add($key, $newValue, $expiration);
                $resultCode = $memcached->getResultCode();
            }

            if ($resultCode !== \Memcached::RES_SUCCESS) {
                throw $this->getExceptionByResultCode($resultCode);
            }
        }

        return $newValue;
    }

    public function delete($key)
    {
        $memcached = $this->getMemcachedResource();
        $result = $memcached->delete($key);

        if ($result === false) {
            $resultCode = $memcached->getResultCode();
            if ($resultCode === \Memcached::RES_NOTFOUND) {
                return false;
            }

            throw $this->getExceptionByResultCode($resultCode);
        }

        return true;
    }

    public function flush()
    {
        return $this->getMemcachedResource()->flush();
    }

    /**
     * Returns the Exception class corresponding to the Memcached error code
     *
     * @see http://uk3.php.net/manual/en/memcached.constants.php for a list of constants
     * @param integer $code
     * @return Exception
     */
    protected function getExceptionByResultCode($code)
    {
        if ($code === \Memcached::RES_SUCCESS) {
            return new \InvalidArgumentException(sprintf('The result code "%d" (SUCCESS) is not an error', $code));
        }

        return new RuntimeException($this->getMemcachedResource()->getResultMessage(), $code);
    }
}
