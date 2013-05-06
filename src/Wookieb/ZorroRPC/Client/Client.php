<?php
namespace Wookieb\ZorroRPC\Client;
use Wookieb\ZorroRPC\Exception\ErrorResponseException;
use Wookieb\ZorroRPC\Exception\FormatException;
use Wookieb\ZorroRPC\Exception\TimeoutException;
use Wookieb\ZorroRPC\MessageTypes;
use Wookieb\ZorroRPC\Exception\RPCException;
use \ZMQSocket;
use \ZMQ;
use \ZMQSocketException;
use \ZMQContext;

/**
 * Implementation of ZorroRPC client
 *
 * @author <wookieb@wp.pl>
 */
class Client implements ClientInterface
{
    private $socket;

    /**
     * @param ZMQSocket $socket instance of ZeroMQ REQ socket
     */
    public function __construct(ZMQSocket $socket)
    {
        if ($socket->getSocketType() !== ZMQ::SOCKET_REQ) {
            throw new \InvalidArgumentException('Invalid zeromq socket type - REQ required');
        }
        $this->socket = $socket;
    }

    /**
     * Create client with newly created ZeroMQ socket
     *
     * @param string $serverAddress rpc server address
     * @param int $timeout response timeout
     * @param ZMQContext $context ZeroMQ context
     * @return Client
     */
    public static function create($serverAddress, $timeout = 5, ZMQContext $context = null)
    {
        $context = $context ? $context : new ZMQContext;
        $socket = $context->getSocket(ZMQ::SOCKET_REQ);
        $socket->setSockOpt(ZMQ::SOCKOPT_RCVTIMEO, $timeout * 1000);
        $socket->connect($serverAddress);

        return new self($socket);
    }

    /**
     * {@inheritDoc}
     */
    public function call($method, array $arguments = array())
    {
        $this->send($this->createRequest(MessageTypes::REQUEST, $method, $arguments));
        $response = $this->parseResponse($this->receive(), MessageTypes::RESPONSE, 'request');
        return isset($response[1]) ? $response[1] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function oneWayCall($method, array $arguments = array())
    {
        $this->send($this->createRequest(MessageTypes::ONE_WAY_CALL, $method, $arguments));
        $this->parseResponse($this->receive(), MessageTypes::ONE_WAY_CALL_ACK, 'one way call');
    }

    /**
     * {@inheritDoc}
     */
    public function ping()
    {
        $message = $this->createRequest(MessageTypes::PING);
        $start = microtime(true);
        $this->send($message);
        $response = $this->receive();
        $responseTime = microtime(true) - $start;
        $this->parseResponse($response, MessageTypes::PONG, 'ping');
        return $responseTime;
    }

    /**
     * {@inheritDoc}
     */
    public function push($method, array $arguments = array())
    {
        $this->send($this->createRequest(MessageTypes::PUSH, $method, $arguments));
        $response = $this->parseResponse($this->receive(), MessageTypes::PUSH_ACK, 'push');
        return isset($response[1]) ? $response[1] : null;
    }

    private function createRequest($type, $method = null, $arguments = null)
    {
        $message = array($type);
        if ($method) {
            $message[1] = $method;
            $message[2] = $arguments;
        }
        return $message;
    }


    private function parseResponse($response, $expectedResponseType, $requestTypeName)
    {
        $this->validateResponse($response);
        if ($response[0] !== $expectedResponseType) {
            $msg = 'Unsupported message type in response to "'.$requestTypeName.'" request';
            throw new FormatException($msg, $response);
        }
        return $response;
    }

    private function validateResponse($response)
    {
        if (!is_array($response)) {
            throw new FormatException('Response must be array', $response);
        }

        if (!isset($response[0])) {
            throw new FormatException('No message type', $response);
        }

        if (!MessageTypes::isValid($response[0])) {
            throw new FormatException('Unsupported message type "'.$response[0].'"', $response);
        }

        $messageType = $response[0];
        if ($messageType === MessageTypes::ERROR) {
            $this->handleErrorResponse($response);
        }
    }

    private function handleErrorResponse($response)
    {
        if (!isset($response[1])) {
            throw new FormatException('Error response must contains error', $response);
        }
        throw new ErrorResponseException('Error response received', $response[1]);
    }

    private function send($message)
    {
        try {
            $this->socket->send($this->encode($message));
        } catch (ZMQSocketException $e) {
            throw new RPCException('Cannot send request', 0, $e);
        }
    }

    private function receive()
    {
        try {
            $message = $this->socket->recv();
        } catch (ZMQSocketException $e) {
            throw new RPCException('Cannot receive response', 0, $e);
        }

        if ($message === false) {
            throw new TimeoutException;
        }
        return $this->decode($message);
    }

    private function encode($data)
    {
        return msgpack_pack($data);
    }

    private function decode($data)
    {
        return msgpack_unpack($data);
    }
}