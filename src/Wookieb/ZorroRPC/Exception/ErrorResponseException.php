<?php
namespace Wookieb\ZorroRPC\Exception;

/**
 * Thrown when client receive response that is an error
 *
 * @author Wookieb\ZorroRPC\Exception
 */
class ErrorResponseException extends RPCException
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