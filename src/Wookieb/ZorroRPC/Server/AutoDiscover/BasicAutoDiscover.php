<?php
namespace Wookieb\ZorroRPC\Server\AutoDiscover;
use \ReflectionObject;
use \ReflectionMethod;
use Wookieb\ZorroRPC\Server\MethodTypes;
use Wookieb\ZorroRPC\Server\Method;

/**
 * Autodiscover that retrieves rpc methods from object where every public method will be treated as RPC method
 *
 * Type recognition based on method name prefix
 * oneWayCall_* => ONE WAY CALL method
 * push_* => PUSH method
 * [without_prefix] => BASIC method
 *
 * @author wookieb <wookieb@wp.pl>
 */
class BasicAutoDiscover implements AutoDiscoverInterface
{
    protected $object;

    /**
     * @param object $object
     */
    public function __construct($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('Cannot discover methods from non object');
        }
        $this->object = $object;
    }

    /**
     * {@inheritDoc}
     */
    public function discover()
    {
        $object = new ReflectionObject($this->object);
        $methods = array();
        foreach ($object->getMethods() as $method) {
            if (!$this->isValidMethod($method)) {
                continue;
            }
            $methods[] = new Method(
                $this->getMethodName($method),
                array($this->object, $method->getName()),
                $this->getMethodType($method)
            );
        }
        return $methods;
    }

    protected function isValidMethod(ReflectionMethod $method)
    {
        return $method->isPublic();
    }

    protected function getMethodType(ReflectionMethod $method)
    {
        $name = $method->getName();
        switch (true) {
            case substr($name, 0, 11) === 'oneWayCall_':
                return MethodTypes::ONE_WAY;
            case
                substr($name, 0, 5) === 'push':
                return MethodTypes::PUSH;
        }
        return MethodTypes::BASIC;
    }

    protected function getMethodName(ReflectionMethod $method)
    {
        $name = $method->getName();
        return preg_replace('/^(oneWayCall_|push_)(.*)/', '$2', $name);
    }
}