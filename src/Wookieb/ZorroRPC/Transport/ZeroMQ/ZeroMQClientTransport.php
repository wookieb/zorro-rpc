<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wookieb
 * Date: 21.05.13
 * Time: 22:15
 * To change this template use File | Settings | File Templates.
 */

namespace Wookieb\ZorroRPC\Transport\ZeroMQ;
use Wookieb\ZorroRPC\Headers\Parser;
use Wookieb\ZorroRPC\Exception\FormatException;
use Wookieb\ZorroRPC\Exception\TimeoutException;
use Wookieb\ZorroRPC\Exception\TransportException;
use Wookieb\ZorroRPC\Transport\ClientTransportInterface;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Response;
use Wookieb\ZorroRPC\Headers\Headers;

class ZeroMQClientTransport implements ClientTransportInterface
{
    /**
     * @var \ZMQSocket
     */
    private $socket;

    public function __construct($servers, $timeout = 1)
    {
        $servers = (array)$servers;
        $this->socket = $this->createSocket($servers);
        $this->setTimeout($timeout);
    }

    protected function createSocket(array $servers)
    {
        $socket = new \ZMQSocket(new \ZMQContext, \ZMQ::SOCKET_REQ);
        $socket->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);
        foreach ($servers as $server) {
            $socket->connect($server);
        }
        return $socket;
    }

    public function setTimeout($timeout)
    {
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_RCVTIMEO, $timeout * 1000);
        return $this;
    }

    public function getTimeout()
    {
        return $this->socket->getSockOpt(\ZMQ::SOCKOPT_RCVTIMEO) / 1000;
    }

    /**
     * {@inheritDoc}
     */
    public function sendRequest(Request $request)
    {
        $message = array(
            $request->getType(),
            (string)$request->getHeaders()
        );

        if ($request->getType() !== MessageTypes::PING) {
            $message[] = $request->getMethodName();
            foreach ($request->getArgumentsBody() as $argument) {
                $message[] = $argument;
            }
        }
        try {
            $this->socket->sendMulti($message);
        } catch (\ZMQSocketException $e) {
            throw new TransportException('Cannot send request', 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function receiveResponse()
    {
        try {
            $result = $this->socket->recvMulti();
        } catch (\ZMQSocketException $e) {
            throw new TransportException('Cannot receive response', 0, $e);
        }

        if ($result === false) {
            throw new TimeoutException('Timeout (' . $this->getTimeout() . 's) reached');
        }

        if (!isset($result[0])) {
            throw new FormatException('Invalid response - no response type', $result);
        }
        $response = new Response();
        $response->setType((int)$result[0]);


        if (!isset($result[1])) {
            throw new FormatException('Invalid response - no headers', $result);
        }

        $response->setHeaders(
            new Headers(Parser::parseHeaders($result[1]))
        );

        if ($response->getType() === MessageTypes::ONE_WAY_CALL_ACK || $response->getType() === MessageTypes::PONG) {
            return $response;
        }

        if (!isset($result[2])) {
            throw new FormatException('Invalid response - no response body', $result);
        }
        return $response->setResultBody($result[2]);
    }
}