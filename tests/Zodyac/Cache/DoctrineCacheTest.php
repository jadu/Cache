<?php

namespace Zodyac\Cache;

class DoctrineCacheTest extends \PHPUnit_Framework_TestCase
{
    public $cache;
    public $doctrineCache;

    public function setUp()
    {
        $this->cache = $this->getMock('Zodyac\Cache\Cache', array('get', 'set', 'delete', 'flush'), array(), '', false);
        $this->doctrineCache = new DoctrineCache($this->cache);
    }

    public function testFetchReturnsCachedData()
    {
        $this->cache->expects($this->once())->method('get')
            ->with($this->equalTo('test'))
            ->will($this->returnValue(new Result('test', true, 'data')));

        $this->assertEquals('data', $this->doctrineCache->fetch('test'));
    }

    public function testFetchReturnsFalseOnCacheMiss()
    {
        $this->cache->expects($this->once())->method('get')
            ->will($this->returnValue(new Result('test', false)));

        $this->assertFalse($this->doctrineCache->fetch('test'));
    }

    public function testContainsReturnsTrueOnCacheHit()
    {
        $this->cache->expects($this->once())->method('get')
            ->with($this->equalTo('test'))
            ->will($this->returnValue(new Result('test', true, 'data')));

        $this->assertTrue($this->doctrineCache->contains('test'));
    }

    public function testContainsReturnsFalseOnCacheMiss()
    {
        $this->cache->expects($this->once())->method('get')
            ->with($this->equalTo('test'))
            ->will($this->returnValue(new Result('test', false)));

        $this->assertFalse($this->doctrineCache->contains('test'));
    }

    public function testSave()
    {
        $this->cache->expects($this->once())->method('set')
            ->with($this->equalTo('test'), $this->equalTo('data'), $this->equalTo(3600))
            ->will($this->returnValue(true));

        $this->assertTrue($this->doctrineCache->save('test', 'data', 3600));
    }

    public function testDelete()
    {
        $this->cache->expects($this->once())->method('delete')
            ->with($this->equalTo('test'))
            ->will($this->returnValue(true));

        $this->assertTrue($this->doctrineCache->delete('test'));
    }

    public function testDeleteAllFlushesTheCache()
    {
        $this->cache->expects($this->once())->method('flush')
            ->will($this->returnValue(true));

        $this->assertTrue($this->doctrineCache->deleteAll());
    }

    public function testFlushAllFlushesTheCache()
    {
        $this->cache->expects($this->once())->method('flush')
            ->will($this->returnValue(true));

        $this->assertTrue($this->doctrineCache->flushAll());
    }

    public function testGetStatsNotSupported()
    {
        $this->assertNull($this->doctrineCache->getStats());
    }
}
