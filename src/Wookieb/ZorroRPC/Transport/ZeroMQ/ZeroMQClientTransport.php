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

    public function __construct(\ZMQSocket $socket, $timeout = 1)
    {
        if ($socket->getSocketType() !== \ZMQ::SOCKET_REQ) {
            throw new \InvalidArgumentException('REQ socket required');
        }
        $this->socket = $socket;
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);
        $this->setTimeout($timeout);
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
            $request->getType()
        );

        if ($request->getType() !== MessageTypes::PING) {
            $message[1] = (string)$request->getHeaders();
            $message[2] = $request->getMethodName();
            $message[3] = $request->getArgumentsBody();
        }
        try {
            print_r($message);
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
            throw new TimeoutException('Timeout ('.$this->getTimeout().'s) reached');
        }

        if (!isset($result[0])) {
            throw new FormatException('Invalid response - no response type', $result);
        }
        $response = new Response();
        $response->setType((int)$result[0]);

        if ($response->getType() === MessageTypes::PONG) {
            return $response;
        }

        if (!isset($result[1])) {
            throw new FormatException('Invalid response - no headers', $result);
        }

        $response->setHeaders(
            new Headers(Parser::parseHeaders($result[1]))
        );

        if ($response->getType() === MessageTypes::ONE_WAY_CALL_ACK) {
            return $response;
        }
        if (!isset($result[2])) {
            throw new FormatException('Invalid response - no response body', $result);
        }
        return $response->setResultBody($result[2]);
    }
}