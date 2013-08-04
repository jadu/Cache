<?php

namespace Zodyac\Cache;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    public $storage;
    public $cache;
    public $logger;

    public function setUp()
    {
        $this->storage = $this->getMock('Zodyac\Cache\Storage\StorageInterface');
        $this->cache = new Cache($this->storage);

        $this->logger = $this->getMock('Psr\Log\LoggerInterface');
    }

    public function testLoggerCanBePassedAsAConstructorArgument()
    {
        $this->storage->expects($this->any())->method('get')
            ->will($this->returnValue(new Result('test', false)));

        $this->logger->expects($this->once())->method('debug');

        $cache = new Cache($this->storage, $this->logger);
        $cache->get('test');
    }

    public function testGetReturnsResult()
    {
        $key = 'test';
        $result = new Result($key, false);

        $this->storage->expects($this->any())->method('get')
            ->with($this->equalTo($key))
            ->will($this->returnValue($result));

        $this->assertSame($result, $this->cache->get($key));
    }

    public function testGetLogsCacheHit()
    {
        $key = 'test';
        $result = new Result($key, true, 'data');

        $this->storage->expects($this->any())->method('get')
            ->with($this->equalTo($key))
            ->will($this->returnValue($result));

        $this->logger->expects($this->once())->method('debug')
            ->with($this->equalTo('Cache hit "test"'));

        $this->cache->setLogger($this->logger);
        $this->cache->get($key);
    }

    public function testGetLogsCacheMiss()
    {
        $key = 'test';
        $result = new Result($key, false);

        $this->storage->expects($this->any())->method('get')
            ->with($this->equalTo($key))
            ->will($this->returnValue($result));

        $this->logger->expects($this->once())->method('debug')
            ->with($this->equalTo('Cache miss "test"'));

        $this->cache->setLogger($this->logger);
        $this->cache->get($key);
    }

    public function testSet()
    {
        $key = 'test';
        $value = 12;
        $expiration = 3600;

        $this->storage->expects($this->once())->method('set')
            ->with($this->equalTo($key), $this->equalTo($value), $this->equalTo($expiration));

        $this->cache->set($key, $value, $expiration);
    }

    public function testSetLogsOperation()
    {
        $this->logger->expects($this->once())->method('debug')
            ->with($this->equalTo('Cache set "test"'));

        $this->cache->setLogger($this->logger);
        $this->cache->set('test', 'value', 3600);
    }

    public function testIncrementReturnsNewValue()
    {
        $key = 'test';

        $this->storage->expects($this->once())->method('increment')
            ->with($this->equalTo($key))
            ->will($this->returnValue(13));

        $this->assertEquals(13, $this->cache->increment($key));
    }

    public function testIncrementLogsOperation()
    {
        $this->storage->expects($this->any())->method('increment')
            ->will($this->returnValue(42));

        $this->logger->expects($this->once())->method('debug')
            ->with($this->equalTo('Cache incremented "test" with new value 42'));

        $this->cache->setLogger($this->logger);
        $this->cache->increment('test', 'value', 3600);
    }

    public function testDelete()
    {
        $key = 'test';

        $this->storage->expects($this->once())->method('delete')
            ->with($this->equalTo($key));

        $this->cache->delete($key);
    }

    public function testDeleteLogsOperation()
    {
        $this->logger->expects($this->once())->method('debug')
            ->with($this->equalTo('Cache delete "test"'));

        $this->cache->setLogger($this->logger);
        $this->cache->delete('test');
    }

    public function testCreateKey()
    {
        $tagKey = 'tag:Site';

        $this->storage->expects($this->once())->method('get')
            ->with($this->equalTo($tagKey))
            ->will($this->returnValue(new Result($tagKey, true, 123456789)));

        $key = $this->cache->createKey('test', array('Site'));
        $this->assertEquals('Site:123456789/test', $key);
    }

    public function testCreateKeyCombinesKeysArray()
    {
        $tagKey = 'tag:Site';

        $this->storage->expects($this->once())->method('get')
            ->with($this->equalTo($tagKey))
            ->will($this->returnValue(new Result($tagKey, true, 123456789)));

        $key = $this->cache->createKey(array('v2', 'test'), array('Site'));
        $this->assertEquals('Site:123456789/v2/test', $key);
    }

    public function testCreateKeyAddsTagCounterWhenNotFound()
    {
        $time = time();
        $tagKey = 'tag:Site';

        $this->storage->expects($this->at(0))->method('get')
            ->with($this->equalTo($tagKey))
            ->will($this->returnValue(new Result($tagKey, false)));

        $this->storage->expects($this->at(1))->method('add')
            ->with($this->equalTo($tagKey), $this->equalTo($time))
            ->will($this->returnValue(true));

        $key = $this->cache->createKey('test', array('Site'));
        $this->assertEquals(sprintf('Site:%d/test', $time), $key);
    }

    public function testCreateKeyUsesExistingTagCounterIfRaceLost()
    {
        $tagKey = 'tag:Site';

        $this->storage->expects($this->at(0))->method('get')
            ->with($this->equalTo($tagKey))
            ->will($this->returnValue(new Result($tagKey, false)));

        $this->storage->expects($this->at(1))->method('add')
            ->will($this->returnValue(false));

        $this->storage->expects($this->at(2))->method('get')
            ->with($this->equalTo($tagKey))
            ->will($this->returnValue(new Result($tagKey, true, 123456789)));

        $key = $this->cache->createKey('test', array('Site'));
        $this->assertEquals('Site:123456789/test', $key);
    }

    public function testCreateKeyUsesCurrentTimeWhenRaceLostButCounterCouldStillNotBeFound()
    {
        $time = time();
        $tagKey = 'tag:Site';

        $this->storage->expects($this->at(0))->method('get')
            ->with($this->equalTo($tagKey))
            ->will($this->returnValue(new Result($tagKey, false)));

        $this->storage->expects($this->at(1))->method('add')
            ->will($this->returnValue(false));

        $this->storage->expects($this->at(2))->method('get')
            ->with($this->equalTo($tagKey))
            ->will($this->returnValue(new Result($tagKey, false)));

        $key = $this->cache->createKey('test', array('Site'));
        $this->assertEquals(sprintf('Site:%d/test', $time), $key);
    }

    public function testCreateKeyWithMultipleTags()
    {
        $this->storage->expects($this->at(0))->method('get')
            ->with($this->equalTo('tag:Site'))
            ->will($this->returnValue(new Result('tag:Site', true, 1)));

        $this->storage->expects($this->at(1))->method('get')
            ->with($this->equalTo('tag:Application'))
            ->will($this->returnValue(new Result('tag:Application', true, 2)));

        $key = $this->cache->createKey('test', array('Site', 'Application'));
        $this->assertEquals('Site:1/Application:2/test', $key);
    }

    public function testInvalidateTagIncrementsTagKey()
    {
        $this->storage->expects($this->once())->method('increment')
            ->with($this->equalTo('tag:Site'));

        $this->cache->invalidateTag('Site');
    }

    public function testInvalidateTagLogsOperation()
    {
        $this->logger->expects($this->once())->method('debug')
            ->with($this->equalTo('Cache invalidate tag "Site"'));

        $this->cache->setLogger($this->logger);
        $this->cache->invalidateTag('Site');
    }

    public function testFlush()
    {
        $this->storage->expects($this->once())->method('flush');

        $this->cache->flush();
    }

    public function testFlushLogsOperation()
    {
        $this->logger->expects($this->once())->method('debug')
            ->with($this->equalTo('Cache flush'));

        $this->cache->setLogger($this->logger);
        $this->cache->flush();
    }
}
