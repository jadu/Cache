<?php

namespace Zodyac\Cache;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Zodyac\Cache\Storage\StorageInterface;

class Cache implements LoggerAwareInterface
{
    protected $storage;
    protected $logger;

    /**
     * Constructor
     *
     * @param StorageInterface $storage The cache storage engine (Memcached, etc.)
     * @param LoggerInterface $logger Logger used to log cache operations and results
     */
    public function __construct(StorageInterface $storage, LoggerInterface $logger = null)
    {
        $this->storage = $storage;
        $this->logger = $logger;
    }

    /**
     * Returns the storage
     *
     * @return StorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Returns the logger instance
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Sets the logger instance
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Retrieves the cached value at the given key.
     *
     * @param string $key
     * @return Result
     */
    public function get($key)
    {
        $result = $this->storage->get($key);

        if ($this->logger) {
            $this->logger->debug(sprintf('Cache %s "%s"', $result->isHit() ? 'hit' : 'miss', $key));
        }

        return $result;
    }

    /**
     * Sets the value in the cache at the given key.
     *
     * @param string $key
     * @param mixed $value
     * @param integer $expiration The time in seconds before the cache key is invalidated
     * @return boolean Returns True if the value was set
     */
    public function set($key, $value, $expiration = null)
    {
        if ($this->logger) {
            $this->logger->debug(sprintf('Cache set "%s"', $key));
        }

        return $this->storage->set($key, $value, $expiration);
    }

    /**
     * Increments the counter at the given key.
     *
     * @param string $key
     * @param integer $value The initial value
     * @param integer $expiration The time in seconds before the cache key is invalidated
     * @return integer The new incremented value (or the initial value if the cache key was not found)
     */
    public function increment($key, $value = null, $expiration = null)
    {
        $newValue = $this->storage->increment($key, $value, $expiration);

        if ($this->logger) {
            $this->logger->debug(sprintf('Cache incremented "%s" with new value %d', $key, $newValue));
        }

        return $newValue;
    }

    /**
     * Deletes the value at the given key.
     *
     * @param string $key
     * @return boolean
     */
    public function delete($key)
    {
        if ($this->logger) {
            $this->logger->debug(sprintf('Cache delete "%s"', $key));
        }

        return $this->storage->delete($key);
    }

    /**
     * Flushes the cache of all data.
     *
     * @return boolean
     */
    public function flush()
    {
        if ($this->logger) {
            $this->logger->debug('Cache flush');
        }

        return $this->storage->flush();
    }

    /**
     * Invalidates tagged cache keys by incrementing the tag prefix counter.
     *
     * @param string $tag
     * @return bool
     */
    public function invalidateTag($tag)
    {
        $key = $this->createTagKey($tag);
        $this->storage->increment($key, time());

        if ($this->logger) {
            $this->logger->debug(sprintf('Cache invalidate tag "%s"', $tag));
        }

        return true;
    }

    /**
     * Creates a cache key prefixed with the given tags
     *
     * @param string|array $key Key string or array of key strings to be combined
     * @param array $tags
     * @return string
     */
    public function createKey($key, array $tags)
    {
        if (is_array($key)) {
            $key = implode('/', $key);
        }

        if (count($tags) > 0) {
            $prefix = '';
            foreach ($tags as $tag) {
                $prefix .= $this->getTagPrefix($tag) . '/';
            }

            $key = $prefix . $key;
        }

        return $key;
    }

    /**
     * Creates the cache key used to store the tag prefix.
     * @param string $tag
     * @return string
     */
    protected function createTagKey($tag)
    {
        return sprintf('tag:%s', $tag);
    }

    /**
     * Returns the current tag prefix.
     *
     * @param string $tag
     * @return string
     */
    protected function getTagPrefix($tag)
    {
        $key = $this->createTagKey($tag);

        $result = $this->storage->get($key);
        if ($result->isMiss()) {
            $counter = time();
            if (!$this->storage->add($key, $counter)) {
                $result = $this->storage->get($key);
                if ($result->isHit()) {
                    $counter = $result->getValue();
                }
            }
        } else {
            $counter = $result->getValue();
        }

        return $tag . ':' . $counter;
    }
}
