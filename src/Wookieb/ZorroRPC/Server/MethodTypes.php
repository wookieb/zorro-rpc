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
    const BASIC = 1;
    const ONE_WAY = 2;
    const PUSH = 3;
}