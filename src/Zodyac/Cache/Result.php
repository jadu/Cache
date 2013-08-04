<?php

namespace Zodyac\Cache;

use Zodyac\Cache\Exception\RuntimeException;

/**
 * Represents a single cache retrieval result
 */
class Result
{
    protected $key;
    protected $hit;
    protected $value;

    public function __construct($key, $hit, $value = null)
    {
        $this->key = $key;
        $this->hit = $hit;
        $this->value = $value;
    }

    /**
     * Returns the cache key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Whether the result was a cache hit.
     *
     * @return boolean
     */
    public function isHit()
    {
        return $this->hit;
    }

    /**
     * Whether the result was a cache miss.
     *
     * @return boolean
     */
    public function isMiss()
    {
        return !$this->hit;
    }

    /**
     * Returns the value retrieved from the cache
     *
     * @throws RuntimeException If the result is a miss
     * @return mixed
     */
    public function getValue()
    {
        if ($this->isMiss()) {
            throw new RuntimeException(sprintf('Expected "%s" to result in a cache hit, miss occurred', $this->getKey()));
        }

        return $this->value;
    }
}
