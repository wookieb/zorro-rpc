<?php
namespace Wookieb\ZorroRPC\Client;
use Wookieb\ZorroRPC\Exception\ErrorResponseException;
use Wookieb\ZorroRPC\Exception\TimeoutException;
use Wookieb\ZorroRPC\Exception\FormatException;
use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Serializer\ClientSerializerInterface;

/**
 * ZorroRPC client interface
 *
 * @author wookieb <wookieb@wp.pl>
 */
interface ClientInterface
{
    /**
     * Perform basic RPC call that return response from remote server
     *
     * @param string $method
     * @param array $arguments
     * @param Headers $headers request headers
     *
     * @return mixed response from remote server
     *
     * @throws ErrorResponseException when something went wrong on the remote server side
     * @throws TimeoutException when timeout occurs :)
     * @throws FormatException when response from server is malformed
     */
    function call($method, array $arguments = array(), Headers $headers = null);

    /**
     * Perform one way call that does not wait for finish of RPC method execution
     *
     * @param string $method
     * @param array $arguments
     * @param Headers $headers request headers
     *
     * @throws TimeoutException when timeout occurs :)
     * @throws FormatException when response from server is malformed
     */
    function oneWayCall($method, array $arguments = array(), Headers $headers = null);

    /**
     * Perform ping to RPC server
     * Returns response time
     *
     * @throws TimeoutException when timeout occurs :)
     * @throws FormatException when response from server is malformed
     *
     * @return float response time
     */
    function ping();

    /**
     * Perform push call
     * Push call is like basic RPC call but this one has ability to return response to the client before RPC method
     * execution ends
     *
     * @param string $method
     * @param array $arguments
     * @param Headers $headers request headers
     * @return mixed
     *
     * @throws ErrorResponseException when something went wrong on the remote server side
     * @throws TimeoutException when timeout occurs :)
     * @throws FormatException when response from server is malformed
     */
    function push($method, array $arguments = array(), Headers $headers = null);

    /**
     * Set default request headers that will be send in each request
     *
     * @param Headers $headers
     * @return self
     */
    function setDefaultHeaders(Headers $headers = null);

    /**
     * @return Headers null if no default headers
     */
    function getDefaultHeaders();

    /**
     * Set serializer
     *
     * @param ClientSerializerInterface $serializer
     * @return self
     */
    function setSerializer(ClientSerializerInterface $serializer);

    /**
     * Return current serializer
     *
     * @return ClientSerializerInterface
     */
    function getSerializer();
}