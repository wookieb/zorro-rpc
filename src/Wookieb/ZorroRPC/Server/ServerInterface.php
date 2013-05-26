<?php
namespace Wookieb\ZorroRPC\Server;
use Psr\Log\LoggerAwareInterface;
use Wookieb\ZorroRPC\Serializer\SerializerAggregatorInterface;
use Wookieb\ZorroRPC\Serializer\ServerSerializerInterface;
use Wookieb\ZorroRPC\Headers\Headers;

/**
 * Interface of ZorroRPC server
 *
 * @author wookieb <wookieb@wp.pl>
 */
interface ServerInterface extends LoggerAwareInterface
{

    /**
     * Set default headers for all responses
     *
     * @param Headers $headers
     * @return mixed
     */
    function setDefaultHeaders(Headers $headers);

    /**
     * Runs ZorroRPC server in infinity loop
     */
    function run();


    /**
     * Handle only one request
     */
    function handleCall();

    /**
     * Register RPC method
     *
     * @param string|Method $name
     * @param callback $callback
     * @param int $type
     *
     * @return self
     */
    function registerMethod($name, $callback, $type = MethodTypes::BASIC);

    /**
     * Register list of RPC methods
     *
     * @param array[Method] $methods
     * @return self
     *
     * @throws \InvalidArgumentException when of elements of array is not instance of Method
     */
    function registerMethods(array $methods);

    /**
     * Set serializer aggregator which contains list of serializers and default serializer
     *
     * @param ServerSerializerInterface $serializer
     * @return self
     */
    function setSerializer(ServerSerializerInterface $serializer);

    /**
     * Returns current serializer
     *
     * @return ServerSerializerInterface
     */
    function getSerializer();
}