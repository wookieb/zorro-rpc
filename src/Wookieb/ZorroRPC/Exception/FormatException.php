<?php
namespace Wookieb\ZorroRPC\Exception;

/**
 * Thrown when client receive malformed response
 *
 * @author Wookieb\ZorroRPC\Exception
 */
class FormatException extends RPCException
{
    public $body;

    public function __construct($message, $body)
    {
        $this->body = $body;
        parent::__construct($message);
    }
}