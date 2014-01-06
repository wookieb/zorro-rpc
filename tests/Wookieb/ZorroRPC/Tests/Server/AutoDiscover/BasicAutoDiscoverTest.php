<?php
namespace Wookieb\ZorroRPC\Tests\Server\AutoDiscover;
use Wookieb\ZorroRPC\Server\AutoDiscover\BasicAutoDiscover;
use Wookieb\ZorroRPC\Server\MethodTypes;
use Wookieb\ZorroRPC\Server\Method;

require_once __DIR__.'/RPCServerMethods.php';

class BasicAutoDiscoverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BasicAutoDiscover
     */
    private $object;

    private $methods;

    protected function setUp()
    {
        $targetObject = new RPCServerMethods();
        $this->object = new BasicAutoDiscover($targetObject);
        $this->methods = $this->object->discover();
    }

    private function assertMethodDiscovered($name, $type)
    {
        $found = false;
        foreach ($this->methods as $method) {
            /* @var $method \Wookieb\ZorroRPC\Server\Method */
            if ($method->getName() === $name && $method->getType() === $type) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Method "'.$name.'" of type "'.$type.'" not discovered');
    }

    public function testDiscoverShouldReturnListOfMethodObjects()
    {
        $traversed = false;
        foreach ($this->methods as $method) {
            $this->assertInstanceOf('Wookieb\ZorroRPC\Server\Method', $method);
            $traversed = true;
        }
        $this->assertTrue($traversed, 'No methods discovered');
    }

    public function testDiscoveringPushMethods()
    {
        $this->assertMethodDiscovered('method1', MethodTypes::PUSH);
        $this->assertMethodDiscovered('method2', MethodTypes::PUSH);
    }

    public function testDiscoveringBasicMethods()
    {
        $this->assertMethodDiscovered('method1', MethodTypes::BASIC);
        $this->assertMethodDiscovered('method2', MethodTypes::BASIC);
    }

    public function testDiscoveringOneWayCallMethods()
    {
        $this->assertMethodDiscovered('method1', MethodTypes::ONE_WAY);
        $this->assertMethodDiscovered('method2', MethodTypes::ONE_WAY);
    }

    public function testTargetObjectMustBeAnObject()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Cannot discover methods from non object');
        new BasicAutoDiscover(false);
    }
}
