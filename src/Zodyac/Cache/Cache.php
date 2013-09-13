<?php

namespace Zodyac\Cache;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Zodyac\Cache\Exception\ExceptionInterface;
use Zodyac\Cache\Result;
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
        try {
            $result = $this->storage->get($key);
        } catch (ExceptionInterface $exception) {
            if ($this->logger) {
                $this->logger->error(sprintf('Cache: Unable to get "%s" due to error "%s"', $key, $exception->getMessage()));
            }

            // Return cache miss
            return new Result($key, false);
        }

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
    public function set($key, $value, $expiration = 0)
    {
        try {
            $result = $this->storage->set($key, $value, $expiration);
        } catch (ExceptionInterface $exception) {
            if ($this->logger) {
                $this->logger->error(sprintf('Cache: Unable to set "%s" due to error "%s"', $key, $exception->getMessage()));
            }

            return false;
        }

        if ($this->logger) {
            $this->logger->debug(sprintf('Cache set "%s"', $key));
        }

        return $result;
    }

    /**
     * Increments the counter at the given key.
     *
     * @param string $key
     * @param integer $value The initial value
     * @param integer $expiration The time in seconds before the cache key is invalidated
     * @return integer The new incremented value or False if the value could not be incremented
     */
    public function increment($key, $value = null, $expiration = 0)
    {
        try {
            $newValue = $this->storage->increment($key, $value, $expiration);
        } catch (ExceptionInterface $exception) {
            if ($this->logger) {
                $this->logger->error(sprintf('Cache: Unable to increment "%s" due to error "%s"', $key, $exception->getMessage()));
            }

            return false;
        }

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
        try {
            $result = $this->storage->delete($key);
        } catch (ExceptionInterface $exception) {
            if ($this->logger) {
                $this->logger->error(sprintf('Cache: Unable to delete "%s" due to error "%s"', $key, $exception->getMessage()));
            }

            return false;
        }

        if ($this->logger) {
            $this->logger->debug(sprintf('Cache delete "%s"', $key));
        }

        return $result;
    }

    /**
     * Flushes the cache of all data.
     *
     * @return boolean
     */
    public function flush()
    {
        try {
            $result = $this->storage->flush();
        } catch (ExceptionInterface $exception) {
            if ($this->logger) {
                $this->logger->error(sprintf('Cache: Unable to flush due to error "%s"', $exception->getMessage()));
            }

            return false;
        }

        if ($this->logger) {
            $this->logger->debug('Cache flush');
        }

        return $result;
    }

    /**
     * Invalidates tagged cache keys by incrementing the tag prefix counter.
     *
     * @param string $tag
     * @return bool
     */
    public function invalidateTag($tag)
    {
        try {
            $key = $this->createTagKey($tag);
            $this->storage->increment($key, time());
        } catch (ExceptionInterface $exception) {
            if ($this->logger) {
                $this->logger->error(sprintf('Cache: Unable to invalidate tag "%s" due to error "%s"', $tag, $exception->getMessage()));
            }

            return false;
        }

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

        try {
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
        } catch (ExceptionInterface $exception) {
            if ($this->logger) {
                $this->logger->error(sprintf('Cache: Unable to get counter for tag "%s" due to error "%s"', $tag, $exception->getMessage()));
            }

            // Use the initial value on error
            $counter = 0;
        }

        return $tag . ':' . $counter;
    }
}
