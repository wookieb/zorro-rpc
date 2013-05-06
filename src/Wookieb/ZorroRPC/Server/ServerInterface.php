<?php
namespace Wookieb\ZorroRPC\Server;

/**
 * Interface of ZorroRPC server
 *
 * @author wookieb <wookieb@wp.pl>
 */
interface ServerInterface
{
    /**
     * Runs ZorroRPC server
     */
    function run();

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
}