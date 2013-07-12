<?php
namespace Wookieb\ZorroRPC\Serializer;


use Wookieb\ZorroRPC\Exception\DataFormatNotFoundException;

/**
 * Interface for serializer used by client
 *
 * @author wookieb <wookieb@wp.pl>
 */
interface ClientSerializerInterface
{
    /**
     * Serialize arguments for given RPC method name
     * Method MUST return array of strings.
     *
     * @param string $method
     * @param array $arguments
     * @param string|null $mimeType target mime type, Null if default mime type
     * @throws DataFormatNotFoundException
     * @return array of serialized arguments
     */
    function serializeArguments($method, array $arguments, $mimeType = null);

    /**
     * Unserialize response body for given RPC method name
     *
     * @param string $method
     * @param string $result
     * @param string|null $mimeType target mime type. Null if default mime type
     * @throws DataFormatNotFoundException
     * @return mixed
     */
    function unserializeResult($method, $result, $mimeType = null);
}