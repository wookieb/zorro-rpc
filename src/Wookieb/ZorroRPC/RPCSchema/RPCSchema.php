<?php

namespace Wookieb\ZorroRPC\RPCSchema;
use Wookieb\ZorroRPC\Exception\NoSuchMethodException;

/**
 * Container for methods definition
 *
 * @author Łukasz Kużyński "wookieb" <lukasz.kuzynski@gmail.com>
 */
class RPCSchema
{
    private $methods;

    /**
     * @param array $methods list of objects of MethodSchema
     */
    public function __construct(array $methods = array())
    {
        foreach ($methods as $method) {
            $this->registerMethod($method);
        }
    }

    /**
     * @param MethodSchema $method
     * @return self
     */
    public function registerMethod(MethodSchema $method)
    {
        $this->methods[$method->getName()] = $method;
        return $this;
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Returns information whether method with given name exists
     *
     * @param string $name
     * @return boolean
     */
    public function hasMethod($name)
    {
        return isset($this->methods[$name]);
    }

    /**
     * Returns method schema for given method name
     *
     * @param string $name
     * @return MethodSchema
     * @throws NoSuchMethodException
     */
    public function getMethod($name)
    {
        if (!$this->hasMethod($name)) {
            throw new NoSuchMethodException('Method "'.$name.'" does not exist in schema');
        }
        return $this->methods[$name];
    }
}
