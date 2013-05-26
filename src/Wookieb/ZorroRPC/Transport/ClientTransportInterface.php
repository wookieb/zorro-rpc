<?php
namespace Wookieb\ZorroRPC\Transport;
use Wookieb\ZorroRPC\Exception\FormatException;
use Wookieb\ZorroRPC\Exception\TimeoutException;

interface ClientTransportInterface
{
    /**
     * Send request from client
     *
     * @param Request $request
     */
    function sendRequest(Request $request);

    /**
     * Receive response from server
     *
     * @return Response
     *
     * @throws TimeoutException
     * @throws FormatException when response is malformed
     */
    function receiveResponse();
}