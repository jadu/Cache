<?php

namespace Zodyac\Cache\Storage;

use Zodyac\Cache\Result;

class ArrayStorageTest extends \PHPUnit_Framework_TestCase
{
    public $storage;

    public function setUp()
    {
        $this->storage = new ArrayStorage();
    }

    public function testGetReturnsHitResultWhenKeyFound()
    {
        $this->storage->set('test', 'value');

        $this->assertEquals(new Result('test', true, 'value'), $this->storage->get('test'));
    }

    public function testGetReturnsMissResultWhenKeyNotFound()
    {
        $this->assertEquals(new Result('test', false), $this->storage->get('test'));
    }

    public function testGetMultiReturnsArrayOfResults()
    {
        $this->storage->set('test', 1);
        $this->storage->set('test2', 2);

        $results = $this->storage->getMulti(array('test', 'test2', 'test3', 'test4'));

        $this->assertEquals(array(
            new Result('test', true, 1),
            new Result('test2', true, 2),
            new Result('test3', false),
            new Result('test4', false),
        ), $results);
    }

    public function testAddReturnsTrueOnSuccess()
    {
        $this->assertTrue($this->storage->add('test', 'value'));
        $this->assertEquals(new Result('test', true, 'value'), $this->storage->get('test'));
    }

    public function testAddReturnsFalseIfKeyExists()
    {
        $this->storage->set('test', 'value');

        $this->assertFalse($this->storage->add('test', 'value'));
    }

    public function testIncrementSetsInitialValueIfKeyNotFound()
    {
        $this->assertEquals(12, $this->storage->increment('test', 12));
        $this->assertEquals(new Result('test', true, 12), $this->storage->get('test'));
    }

    public function testIncrementUpdatesValue()
    {
        $this->storage->set('test', 1);

        $this->assertEquals(2, $this->storage->increment('test', 12));
    }

    public function testDeleteReturnsTrueOnSuccess()
    {
        $this->storage->set('test', 1);

        $this->assertTrue($this->storage->delete('test'));
    }

    public function testDeleteReturnsFalseWhenKeyNotFound()
    {
        $this->assertFalse($this->storage->delete('test'));
    }

    public function testFlushClearsAllCachedData()
    {
        $this->storage->set('test', 1);
        $this->storage->set('test2', 1);
        $this->storage->set('test3', 1);

        $this->storage->flush();

        $this->assertEquals(new Result('test', false), $this->storage->get('test'));
        $this->assertEquals(new Result('test2', false), $this->storage->get('test2'));
        $this->assertEquals(new Result('test3', false), $this->storage->get('test3'));
    }
}
