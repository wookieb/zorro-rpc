<?php
namespace Wookieb\ZorroRPC;

/**
 * Simple implementation of class constants dictionary
 *
 * @author wookieb <wookieb@wp.pl>
 */
abstract class Dictionary
{
    private static $cache = array();

    /**
     * Check whether current class contain given value
     *
     * @param integer $value
     * @return bool
     */
    public static function isValid($value)
    {
        return in_array($value, static::getAll());
    }

    /**
     * Return list of dictionary values
     *
     * @return array
     */
    public static function getAll()
    {
        $class = get_called_class();

        if (!isset(self::$cache[$class])) {
            $classReflection = new \ReflectionClass(get_called_class());
            self::$cache[$class] = $classReflection->getConstants();
        }
        return self::$cache[$class];
    }

    /**
     * Return the name of given value
     *
     * @param mixed $value
     * @return string
     * @throws \OutOfBoundsException
     */
    public static function getName($value)
    {
        $key = array_search($value, self::getAll(), true);
        if ($key === false) {
            throw new \OutOfBoundsException('There is no name in dictionary for given value "' . $value . '"');
        }
        return $key;
    }
}