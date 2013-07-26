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
     * @return mixed
     */
    function unserialize($data);

    /**
     * Returns supported mime types
     *
     * @return array
     */
    function getMimeTypes();
}