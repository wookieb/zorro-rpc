<?php
namespace Wookieb\ZorroRPC\Exception;

/**
 * Thrown when client receive error response
 *
 * @author wookieb <wookieb@wp.pl>
 */
class ErrorResponseException extends ZorroRPCException
{
    private $error;

    public function __construct($message, $error)
    {
        parent::__construct($message);
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }
}