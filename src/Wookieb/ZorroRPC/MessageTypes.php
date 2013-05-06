<?php
namespace Wookieb\ZorroRPC;

/**
 * Dictionary for ZorroRPC message types
 *
 * @author wookieb <wookieb@wp.pl>
 */
class MessageTypes extends Dictionary
{
    const REQUEST = 1;
    const RESPONSE = 2;
    const PING = 3;
    const PONG = 4;
    const ONE_WAY_CALL = 5;
    const ONE_WAY_CALL_ACK = 6;
    const ERROR = 7;
    const PUSH = 8;
    const PUSH_ACK = 9;

    protected static $types;
}