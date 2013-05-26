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
            throw new \InvalidArgumentException('REP socket required');
        }
        $this->socket = $socket;
    }


    /**
     * {@inheritDoc}
     */
    public function receiveRequest()
    {
        $message = $this->socket->recvMulti();
        $this->waitingForResponse = true;
        $requestType = (int)$message[0];
        if (!MessageTypes::isValid($requestType)) {
            throw new FormatException('Invalid request type "'.$requestType.'"', $message);
        }

        $request = new Request();
        $request->setType($requestType);
        if ($requestType !== MessageTypes::PING) {
            $request->setHeaders(new Headers(Parser::parseHeaders(@$message[1])));
            if (empty($message[2])) {
                throw new FormatException('Method name is empty', $message);
            }
            $request->setMethodName($message[2]);
            $request->setArgumentsBody(@$message[3]);
        }
        return $request;
    }

    /**
     * {@inheritDoc}
     */
    public function sendResponse(Response $response)
    {
        $message = array(
            $response->getType()
        );
        print_r($response);
        if ($response->getType() !== MessageTypes::PONG) {
            $message[1] = (string)$response->getHeaders();
            if ($response->getType() !== MessageTypes::ONE_WAY_CALL_ACK) {
                $message[2] = $response->getResultBody();
            }
        }

        $this->waitingForResponse = false;
        $this->socket->sendMulti($message);
    }

    /**
     * {@inheritDoc}
     */
    public function isWaitingForResponse()
    {
        return $this->waitingForResponse;
    }


}