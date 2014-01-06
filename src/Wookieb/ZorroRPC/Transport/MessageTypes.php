<?php
namespace Wookieb\ZorroRPC\Transport;

use Wookieb\ZorroRPC\Dictionary;

/**
 * Dictionary for ZorroRPC message types
 *
 * @author wookieb <wookieb@wp.pl>
 */
class MessageTypes extends Dictionary
{
    const REQUEST = 'request';
    const RESPONSE = 'response';
    const PING = 'ping';
    const PONG = 'pong';
    const ONE_WAY_CALL = 'one-way-call';
    const ONE_WAY_CALL_ACK = 'one-way-call-ack';
    const ERROR = 'error';
    const PUSH = 'push';
    const PUSH_ACK = 'push-ack';

    private static $responseTypes = array(
        self::RESPONSE,
        self::PONG,
        self::ONE_WAY_CALL_ACK,
        self::ERROR,
        self::PUSH_ACK
    );

    private static $responseTypesWithResult = array(
        self::RESPONSE,
        self::PUSH_ACK,
        self::ERROR
    );

    public static function isResponseType($type)
    {
        return in_array($type, self::$responseTypes);
    }

    public static function isResponseTypeWithResult($type)
    {
        return self::isResponseType($type) && in_array($type, self::$responseTypesWithResult);
    }

    protected static $types;
}
