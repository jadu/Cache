<?php

namespace Zodyac\Cache;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function testIsHitReturnsTrueWhenHit()
    {
        $result = new Result('test', true, 1);

        $this->assertTrue($result->isHit());
    }

    public function testIsHitReturnsFalseWhenMiss()
    {
        $result = new Result('test', false);

        $this->assertFalse($result->isHit());
    }

    public function testIsMissReturnsTrueWhenMiss()
    {
        $result = new Result('test', false);

        $this->assertTrue($result->isMiss());
    }

    public function testIsMissReturnsFalseWhenHit()
    {
        $result = new Result('test', true, 1);

        $this->assertFalse($result->isMiss());
    }

    public function testGetValueReturnsValue()
    {
        $result = new Result('test', true, 1);

        $this->assertEquals(1, $result->getValue());
    }

    /**
     * @expectedException Zodyac\Cache\Exception\RuntimeException
     */
    public function testGetValueThrowsExceptionWhenMiss()
    {
        $result = new Result('test', false);

        $result->getValue();
    }
}
