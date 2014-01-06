<?php
namespace Wookieb\ZorroRPC\Server;
use Wookieb\ZorroRPC\Dictionary;

/**
 * Dictionary of RPC method types
 *
 * @author wookieb <wookieb@wp.pl>
 */
class MethodTypes extends Dictionary
{
    const BASIC = 'basic';
    const ONE_WAY = 'one-way';
    const PUSH = 'push';
}
