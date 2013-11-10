<?php
namespace Wookieb\ZorroRPC\Tests;
/**
 * @author Łukasz Kużyński "wookieb" <lukasz.kuzynski@gmail.com>
 */
class ZorroRPCUnit extends \PHPUnit_Framework_TestCase
{
    protected $object;

    public function assertMethodChaining($result, $methodName)
    {
        $this->assertSame($this->object, $result, 'Method chaining violation at "'.$methodName.'"');
    }

    public function notExists($message = 'not exist', $class = '\OutOfRangeException')
    {
        $this->setExpectedException($class, $message);
    }
} 
