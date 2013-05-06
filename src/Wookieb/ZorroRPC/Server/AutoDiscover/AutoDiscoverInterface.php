<?php
namespace Wookieb\ZorroRPC\Server\AutoDiscover;
use Wookieb\ZorroRPC\Server\Method;

/**
 * RPC methods autodiscover
 *
 * @author wookieb <wookieb@wp.pl>
 */
interface AutoDiscoverInterface
{
    /**
     * Returns list of discovered RPC methods
     *
     * @return array[Method]
     */
    function discover();
}