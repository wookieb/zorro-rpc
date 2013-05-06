<?php
namespace Wookieb\ZorroRPC;

/**
 * Simple implementation of class constants dictionary
 *
 * @author wookieb <wookieb@wp.pl>
 */
abstract class Dictionary
{
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
        if (!static::$types) {
            $class = new \ReflectionClass(get_called_class());
            static::$types = $class->getConstants();
        }
        return static::$types;
    }
}