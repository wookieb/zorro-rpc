<?php
namespace Wookieb\ZorroRPC\Exception;

/**
 * Special internal exception for server
 * Thrown when internal server error occurs
 *
 * @author wookieb <wookieb@wp.pl>
 */
class ServerException extends ZorroRPCException
{
    private $sentToClient = false;

    /**
     * @param string $message
     * @param int $sentToClient is error was sent to the client?
     * @param \Exception $previous
     */
    public function __construct($message, $sentToClient, \Exception $previous)
    {
        $this->sentToClient = (bool)$sentToClient;
        parent::__construct($message, null, $previous);
    }

    /**
     * Return info whether exception was sent to the client
     *
     * @return bool
     */
    public function wasSentToClient()
    {
        return $this->sentToClient;
    }
}