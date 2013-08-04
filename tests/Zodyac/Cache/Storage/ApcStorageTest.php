<?php

namespace Zodyac\Cache\Storage;

use Zodyac\Cache\Result;

/**
 * @functional
 *
 * Tests will fail unless apc.enable_cli is on
 */
class ApcStorageTest extends \PHPUnit_Framework_TestCase
{
    public $storage;

    public function setUp()
    {
        if (!function_exists('apc_clear_cache')) {
            $this->markTestSkipped('Enable APC to run the ApcStorage tests.');
        }

        // Clear the user cache
        apc_clear_cache('user');

        $this->storage = new ApcStorage();
    }

    public function testGetReturnsMissResultWhenNotFound()
    {
        $this->assertEquals(new Result('test', false), $this->storage->get('test'));
    }

    public function testGetReturnsHitResultWhenFound()
    {
        $this->storage->set('test', 'value');

        $this->assertEquals(new Result('test', true, 'value'), $this->storage->get('test'));
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

    public function testAddReturnsFalseIfKeyAlreadyExists()
    {
        $this->storage->set('test', 'value');

        $this->assertFalse($this->storage->add('test', 'value'));
    }

    public function testAddReturnsTrueOnSuccess()
    {
        $this->assertTrue($this->storage->add('test', 'value'));

        $this->assertEquals(new Result('test', true, 'value'), $this->storage->get('test'));
    }

    public function testIncrementReturnsNewValue()
    {
        $this->storage->set('test', 12);

        $this->assertEquals(13, $this->storage->increment('test'));
    }

    public function testIncrementAddsValueIfKeyNotAlreadySet()
    {
        $this->assertEquals(12, $this->storage->increment('test', 12));
    }

    public function testIncrementMultipleTimes()
    {
        $this->assertEquals(12, $this->storage->increment('test', 12));
        $this->assertEquals(13, $this->storage->increment('test', 12));
        $this->assertEquals(14, $this->storage->increment('test', 12));
        $this->assertEquals(15, $this->storage->increment('test', 12));
        $this->assertEquals(16, $this->storage->increment('test', 12));
    }

    public function testDelete()
    {
        $this->storage->set('test', 12);

        $this->assertTrue($this->storage->delete('test'));
    }

    public function testDeleteReturnsFalseIfKeyNotSet()
    {
        $this->assertFalse($this->storage->delete('test'));
    }

    public function testFlushClearsUserCache()
    {
        $this->storage->set('test', 1);
        $this->storage->set('test2', 2);
        $this->storage->set('test3', 3);

        $this->storage->flush();

        $this->assertEquals(new Result('test', false), $this->storage->get('test'));
        $this->assertEquals(new Result('test2', false), $this->storage->get('test2'));
        $this->assertEquals(new Result('test3', false), $this->storage->get('test3'));
    }
}
