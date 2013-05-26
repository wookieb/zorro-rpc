<?php

namespace Wookieb\ZorroRPC\Transport;
use Wookieb\ZorroRPC\Exception\FormatException;

interface ServerTransportInterface
{
    /**
     * Receive request from client
     *
     * @return Request
     *
     * @throws FormatException when request is malformed
     */
    function receiveRequest();

    /**
     * Check whether transport layer is waiting for response
     * @return boolean
     */
    function isWaitingForResponse();

    /**
     * Send response from server
     *
     * @param Response $response
     */
    function sendResponse(Response $response);
}