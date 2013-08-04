<?php

namespace Zodyac\Cache;

use Doctrine\Common\Cache\Cache as DoctrineCacheInterface;

/**
 * Bridge for Doctrine common cache
 */
class DoctrineCache implements DoctrineCacheInterface
{
    protected $cache;

    /**
     * Constructor
     *
     * @param Cache $cache The inner cache service
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function fetch($id)
    {
        $result = $this->cache->get($id);

        if ($result->isMiss()) {
            return false;
        }

        return $result->getValue();
    }

    public function contains($id)
    {
        $result = $this->cache->get($id);

        return $result->isHit();
    }

    public function save($id, $data, $lifeTime = 0)
    {
        return $this->cache->set($id, $data, $lifeTime);
    }

    public function delete($id)
    {
        return $this->cache->delete($id);
    }

    public function deleteAll()
    {
        return $this->cache->flush();
    }

    public function flushAll()
    {
        return $this->cache->flush();
    }

    public function getStats()
    {
        // Not supported
    }
}
