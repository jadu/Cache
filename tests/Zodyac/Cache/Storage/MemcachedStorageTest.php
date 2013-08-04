<?php

namespace Zodyac\Cache\Storage;

use Zodyac\Cache\Result;

class MemcachedStorageTest extends \PHPUnit_Framework_TestCase
{
    public $memcached;
    public $storage;

    public function setUp()
    {
        $this->memcached = $this->getMock('Memcached');
        $this->storage = new MemcachedStorage($this->memcached);
    }

    public function testGetReturnsHitResultWhenKeyFound()
    {
        $this->memcached->expects($this->any())->method('get')
            ->will($this->returnValue(12));

        $this->memcached->expects($this->any())->method('getResultCode')
            ->will($this->returnValue(\Memcached::RES_SUCCESS));

        $result = new Result('test', true, 12);
        $this->assertEquals($result, $this->storage->get('test'));
    }

    public function testGetReturnsMissResultWhenKeyNotFound()
    {
        $this->memcached->expects($this->any())->method('get')
            ->will($this->returnValue(false));

        $this->memcached->expects($this->any())->method('getResultCode')
            ->will($this->returnValue(\Memcached::RES_NOTFOUND));

        $result = new Result('test', false);
        $this->assertEquals($result, $this->storage->get('test'));
    }

    /**
     * @expectedException Zodyac\Cache\Exception\RuntimeException
     */
    public function testGetThrowsExceptionWhenMemcachedResultsInAnError()
    {
        $this->memcached->expects($this->any())->method('getResultCode')
            ->will($this->returnValue(\Memcached::RES_TIMEOUT));

        $this->storage->get('test');
    }

    public function testGetMultiReturnsArrayOfResults()
    {
        $this->memcached->expects($this->any())->method('getMulti')
            ->will($this->returnValue(array(
                'test' => 1,
                'test2' => 2
            )));

        $results = $this->storage->getMulti(array('test', 'test2', 'test3', 'test4'));

        $this->assertEquals(array(
            new Result('test', true, 1),
            new Result('test2', true, 2),
            new Result('test3', false),
            new Result('test4', false),
        ), $results);
    }

    /**
     * @expectedException Zodyac\Cache\Exception\RuntimeException
     */
    public function testGetMultiThrowsExceptionWhenMemcachedResultsInAnError()
    {
        $this->memcached->expects($this->any())->method('getMulti')
            ->will($this->returnValue(false));

        $this->memcached->expects($this->any())->method('getResultCode')
            ->will($this->returnValue(\Memcached::RES_TIMEOUT));

        $this->storage->getMulti(array('test', 'test2', 'test3', 'test4'));
    }

    public function testSetReturnsTrueOnSuccess()
    {
        $this->memcached->expects($this->once())->method('set')
            ->with($this->equalTo('test'), $this->equalTo('value'), $this->equalTo(3600))
            ->will($this->returnValue(true));

        $this->assertTrue($this->storage->set('test', 'value', 3600));
    }

    public function testSetHasNoExpirationByDefault()
    {
        $this->memcached->expects($this->once())->method('set')
            ->with($this->anything(), $this->anything(), $this->equalTo(null))
            ->will($this->returnValue(true));

        $this->assertTrue($this->storage->set('test', 'value'));
    }

    /**
     * @expectedException Zodyac\Cache\Exception\RuntimeException
     * @expectedExceptionMessage Connection time out
     * @expectedExceptionCode 31
     */
    public function testSetThrowsExceptionOnFailure()
    {
        $this->memcached->expects($this->any())->method('set')
            ->will($this->returnValue(false));

        $this->memcached->expects($this->any())->method('getResultCode')
            ->will($this->returnValue(\Memcached::RES_TIMEOUT)); // Code 31

        $this->memcached->expects($this->any())->method('getResultMessage')
            ->will($this->returnValue('Connection time out'));

        $this->storage->set('test', 'value');
    }

    public function testAddReturnsTrueOnSuccess()
    {
        $this->memcached->expects($this->once())->method('add')
            ->with($this->equalTo('test'), $this->equalTo('value'), $this->equalTo(3600))
            ->will($this->returnValue(true));

        $this->assertTrue($this->storage->add('test', 'value', 3600));
    }

    public function testAddReturnsFalseWhenNotStored()
    {
        $this->memcached->expects($this->any())->method('add')
            ->will($this->returnValue(false));

        $this->memcached->expects($this->any())->method('getResultCode')
            ->will($this->returnValue(\Memcached::RES_NOTSTORED));

        $this->assertFalse($this->storage->add('test', 'value', 3600));
    }

    /**
     * @expectedException Zodyac\Cache\Exception\RuntimeException
     * @expectedExceptionMessage Connection time out
     * @expectedExceptionCode 31
     */
    public function testAddThrowsExceptionOnFailure()
    {
        $this->memcached->expects($this->any())->method('add')
            ->will($this->returnValue(false));

        $this->memcached->expects($this->any())->method('getResultCode')
            ->will($this->returnValue(\Memcached::RES_TIMEOUT)); // Code 31

        $this->memcached->expects($this->any())->method('getResultMessage')
            ->will($this->returnValue('Connection time out'));

        $this->storage->add('test', 'value');
    }

    public function testIncrementReturnsNewValue()
    {
        $this->memcached->expects($this->once())->method('increment')
            ->with($this->equalTo('test'), $this->equalTo(1))
            ->will($this->returnValue(1));

        $this->assertEquals(1, $this->storage->increment('test', 0));
    }

    public function testIncrementAttemptsToAddTheDefaultValueIfIncrementFailed()
    {
        $this->memcached->expects($this->at(0))->method('increment')
            ->will($this->returnValue(false));

        $this->memcached->expects($this->at(1))->method('getResultCode')
            ->will($this->returnValue(\Memcached::RES_NOTFOUND));

        $this->memcached->expects($this->at(2))->method('add')
            ->with($this->equalTo('test'), $this->equalTo(12))
            ->will($this->returnValue(true));

        $this->memcached->expects($this->at(3))->method('getResultCode')
            ->will($this->returnValue(\Memcached::RES_SUCCESS));

        $this->assertEquals(12, $this->storage->increment('test', 12));
    }

    /**
     * @expectedException Zodyac\Cache\Exception\RuntimeException
     * @expectedExceptionMessage Connection time out
     * @expectedExceptionCode 31
     */
    public function testIncrementThrowsExceptionIfBothIncrementAndAddFailed()
    {
        $this->memcached->expects($this->at(0))->method('increment')
            ->will($this->returnValue(false));

        $this->memcached->expects($this->at(1))->method('getResultCode')
            ->will($this->returnValue(\Memcached::RES_NOTFOUND));

        $this->memcached->expects($this->at(2))->method('add')
            ->will($this->returnValue(true));

        $this->memcached->expects($this->at(3))->method('getResultCode')
            ->will($this->returnValue(\Memcached::RES_TIMEOUT)); // Code 31

        $this->memcached->expects($this->at(4))->method('getResultMessage')
            ->will($this->returnValue('Connection time out'));

        $this->storage->increment('test', 12);
    }

    public function testDeleteReturnsTrueOnSuccess()
    {
        $this->memcached->expects($this->once())->method('delete')
            ->with($this->equalTo('test'))
            ->will($this->returnValue(true));

        $this->assertTrue($this->storage->delete('test'));
    }

    public function testDeleteReturnsFalseIfKeyNotFound()
    {
        $this->memcached->expects($this->any())->method('delete')
            ->will($this->returnValue(false));

        $this->memcached->expects($this->any())->method('getResultCode')
            ->will($this->returnValue(\Memcached::RES_NOTFOUND));

        $this->assertFalse($this->storage->delete('test'));
    }

    /**
     * @expectedException Zodyac\Cache\Exception\RuntimeException
     * @expectedExceptionMessage Connection time out
     * @expectedExceptionCode 31
     */
    public function testDeleteThrowsExceptionOnFailure()
    {
        $this->memcached->expects($this->any())->method('delete')
            ->will($this->returnValue(false));

        $this->memcached->expects($this->any())->method('getResultCode')
            ->will($this->returnValue(\Memcached::RES_TIMEOUT)); // Code 31

        $this->memcached->expects($this->any())->method('getResultMessage')
            ->will($this->returnValue('Connection time out'));

        $this->storage->delete('test');
    }

    /**
     * This is an edge case that shouldn't ever happen assuming the Memcached class works as documented
     *
     * @expectedException InvalidArgumentException
     */
    public function testDeleteThrowsInvalidArgumentExceptionIfMemcachedResultsFalseButSuccessResultCode()
    {
        $this->memcached->expects($this->any())->method('delete')
            ->will($this->returnValue(false));

        $this->memcached->expects($this->any())->method('getResultCode')
            ->will($this->returnValue(\Memcached::RES_SUCCESS));

        $this->storage->delete('test');
    }

    public function testFlush()
    {
        $this->memcached->expects($this->once())->method('flush');

        $this->storage->flush();
    }
}
