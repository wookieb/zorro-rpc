<?php

namespace Wookieb\ZorroRPC\Serializer;
use Wookieb\ZorroRPC\Exception\DataFormatNotFoundException;
use Wookieb\ZorroRPC\Exception\SerializationException;

/**
 * Interface for serializer used by server
 *
 * @author wookieb <wookieb@wp.pl>
 */
interface ServerSerializerInterface
{
    /**
     * Unserialize arguments body for given RPC method name
     *
     * @param string $method
     * @param array $arguments
     * @param string|null $mimeType target mime type. Null if default mime type
     * @throws DataFormatNotFoundException
     * @throws SerializationException
     * @return array
     */
    function unserializeArguments($method, array $arguments, $mimeType = null);

    /**
     * Serialize response for given RPC method name
     *
     * @param string $method method name could be empty when request is malformed
     * @param string $result
     * @param string|null $mimeType target mime type. Null if default mime type
     * @throws DataFormatNotFoundException
     * @throws SerializationException
     * @return string
     */
    function serializeResult($method, $result, $mimeType = null);
}