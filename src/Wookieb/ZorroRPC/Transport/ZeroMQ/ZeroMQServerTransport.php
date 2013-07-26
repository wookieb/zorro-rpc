<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wookieb
 * Date: 20.05.13
 * Time: 21:24
 * To change this template use File | Settings | File Templates.
 */

namespace Wookieb\ZorroRPC\Transport\ZeroMQ;
use Wookieb\ZorroRPC\Exception\FormatException;
use Wookieb\ZorroRPC\Exception\TimeoutException;
use Wookieb\ZorroRPC\Exception\TransportException;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\Response;
use Wookieb\ZorroRPC\Headers\Parser;
use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Transport\ServerTransportInterface;

class ZeroMQServerTransport implements ServerTransportInterface
{
    private $socket;

    private $waitingForResponse = false;

    public function __construct(\ZMQSocket $socket)
    {
        if ($socket->getSocketType() !== \ZMQ::SOCKET_REP) {
            throw new \InvalidArgumentException('Invalid socket type. REP required');
        }
        $this->socket = $socket;
    }

    public static function create($address)
    {
        $socket = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_REP);
        $socket->bind($address);
        return new self($socket);
    }


    /**
     * {@inheritDoc}
     */
    public function receiveRequest()
    {
        try {
            $message = $this->socket->recvMulti();
        } catch (\Exception $e) {
            throw new TransportException('Unable to receive request', null, $e);
        }
        $this->waitingForResponse = true;
        $requestType = (int)$message[0];
        if (!MessageTypes::isValid($requestType)) {
            throw new FormatException('Invalid request type "'.$requestType.'"', $message);
        }

        $request = new Request($requestType);
        $request->setHeaders(new Headers(Parser::parseHeaders(@$message[1])));
        if ($requestType !== MessageTypes::PING) {
            if (empty($message[2])) {
                throw new FormatException('Method name is empty', $message);
            }
            $request->setMethodName($message[2]);

            $arguments = array_slice($message, 3);
            $request->setArgumentsBody($arguments);
        }
        return $request;
    }

    /**
     * {@inheritDoc}
     */
    public function sendResponse(Response $response)
    {
        $message = array(
            $response->getType(),
            (string)$response->getHeaders()
        );

        if ($response->getType() !== MessageTypes::PONG && $response->getType() !== MessageTypes::ONE_WAY_CALL_ACK) {
            $message[2] = $response->getResultBody();
        }

        try {
            $this->socket->sendMulti($message);
            $this->waitingForResponse = false;
        } catch (\Exception $e) {
            $this->waitingForResponse = false;
            throw new TransportException('Unable to send request', null, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isWaitingForResponse()
    {
        return $this->waitingForResponse;
    }
}