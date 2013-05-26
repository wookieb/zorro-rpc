<?php
namespace Wookieb\ZorroRPC\Exception;

/**
 * Thrown when client or server receive malformed message
 *
 * @author wookieb <wookieb@wp.pl>
 */
class FormatException extends TransportException
{
    public $body;

    public function __construct($message, $body)
    {
        $this->body = $body;
        parent::__construct($message);
    }
}