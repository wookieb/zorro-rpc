<?php

namespace Wookieb\ZorroRPC\Serializer\DataFormat;

/**
 * Serializer data format interface
 *
 * @author wookieb <wookieb@wp.pl>
 */
interface DataFormatInterface
{
    /**
     * Serialize data to string
     *
     * @param mixed $data
     * @return string
     */
    function serialize($data);

    /**
     * Deserialize data from string
     *
     * @param string $data
     * @param string|null $class target class name, if null then serializer MAY try to guess deserialized data type
     * @return mixed
     */
    function unserialize($data, $class = null);

    /**
     * Returns supported mime types
     *
     * @return array
     */
    function getMimeTypes();
}