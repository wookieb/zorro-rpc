<?php

namespace Wookieb\ZorroRPC\Exception;

/**
 * @author Łukasz Kużyński "wookieb" <lukasz.kuzynski@gmail.com>
 */
class ExceptionChanger
{
    /**
     * @var \ReflectionProperty[]
     */
    private static $properties = array();

    private static function replaceProperty(\Exception $e, $propertyName, $propertyValue)
    {
        $reflectionProperty = self::getReflectionProperty($propertyName);
        $reflectionProperty->setValue($e, $propertyValue);
        if ($e->getPrevious()) {
            self::replaceProperty($e->getPrevious(), $propertyName, $propertyValue);
        }
    }

    /**
     * @return \ReflectionProperty
     */
    private static function getReflectionProperty($name)
    {
        if (!isset(self::$properties[$name])) {
            $reflection = new \ReflectionClass('\Exception');
            $property = $reflection->getProperty($name);
            $property->setAccessible(true);
            self::$properties[$name] = $property;
        }
        return self::$properties[$name];
    }

    /**
     * Clean out exception from data that are unnecessary for client
     *
     * @param \Exception $e
     */
    public static function clean(\Exception $e)
    {
        self::replaceProperty($e, 'trace', array());
        self::replaceProperty($e, 'file', '');
        self::replaceProperty($e, 'line', 0);
        if (isset($e->xdebug_message)) {
            unset($e->xdebug_message);
        }
    }

    /**
     * Enchant exception with given trace
     * That means stack of every exception will be changes to given trace
     * Line and file properties will take value of keys 'line' and 'file' from first entry of trace
     *
     * @param \Exception $e
     * @param array $trace
     */
    public static function enchantWithTrace(\Exception $e, array $trace)
    {
        $line = 0;
        $file = '';
        if ($trace) {
            $firstEntry = reset($trace);
            $line = $firstEntry['line'];
            $file = $firstEntry['file'];
        }
        self::replaceProperty($e, 'file', $file);
        self::replaceProperty($e, 'line', $line);
        self::replaceProperty($e, 'trace', $trace);

        try {
            self::replaceProperty($e, 'xdebug_message', '');
        } catch (\ReflectionException $e) {
        }
    }
}
