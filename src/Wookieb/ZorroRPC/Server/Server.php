<?php
namespace Wookieb\ZorroRPC\Server;
use Wookieb\ZorroRPC\Exception\FormatException;
use Wookieb\ZorroRPC\Exception\RPCException;
use Wookieb\ZorroRPC\Exception\NoSuchMethodException;
use Wookieb\ZorroRPC\MessageTypes;
use \ZMQSocket;
use \ZMQSocketException;
use \ZMQContext;
use \ZMQ;

/**
 * Basic implementation of ZorroRPC server
 *
 * @author wookieb <wookieb@wp.pl>
 */
class Server implements ServerInterface
{
    private $socket;

    private $methods = array();

    /**
     * @param ZMQSocket $socket instance of ZeroMQ REP socket
     */
    public function __construct(ZMQSocket $socket)
    {
        if ($socket->getSocketType() !== ZMQ::SOCKET_REP) {
            throw new RPCException('Invalid zeromq socket type - REP required');
        }
        $this->socket = $socket;
    }

    /**
     * Creates instance of Server with newly created socket
     *
     * @param string $bindAddress address to listen for
     * @param ZMQContext $context
     * @return self
     */
    public static function create($bindAddress, ZMQContext $context = null)
    {
        $context = $context ? $context : new ZMQContext();
        $socket = $context->getSocket(ZMQ::SOCKET_REP);
        $socket->bind($bindAddress);
        return new self($socket);
    }

    /**
     * {@inheritDoc}
     */
    public function registerMethod($name, $callback = null, $type = MethodTypes::BASIC)
    {
        if (!$name instanceof Method) {
            $name = new Method($name, $callback, $type);
        }

        $this->methods[$name->getType()][$name->getName()] = $name;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function registerMethods(array $methods)
    {
        foreach ($methods as $method) {
            if (!$method instanceof Method) {
                throw new \InvalidArgumentException('Every element of methods list must be instance of Method');
            }
            $this->registerMethod($method);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function run()
    {
        while (true) {
            try {
                $request = $this->decode($this->socket->recv());
                $this->validateRequest($request);

                if ($request[0] === MessageTypes::PING) {
                    $this->respond($this->createResponse(MessageTypes::PONG, null));
                    continue;
                }
                $method = $request[1];
                $arguments = $request[2];
                $this->runMethod($method, $arguments, $request[0]);
            } catch (\Exception $e) {
                // TODO :)
            }
        }
    }

    private function decode($message)
    {
        return msgpack_unpack($message);
    }

    private function validateRequest($request)
    {
        if (!is_array($request)) {
            throw new FormatException('Invalid request format - not array', $request);
        }

        if (!isset($request[0])) {
            throw new FormatException('Invalid request format - no request type', $request);
        }

        if (!MessageTypes::isValid($request[0])) {
            throw new FormatException('Invalid request format - unsupported request type', $request);
        }

        // no method name = PING request
        if (!isset($request[1])) {
            return;
        }

        if (!isset($request[2])) {
            throw new FormatException('Invalid request format - no arguments', $request);
        }
    }

    private function createResponse($type, $response)
    {
        return $this->encode(array(
            $type, $response
        ));
    }

    private function respond($data)
    {
        $this->socket->send($data);
    }

    private function encode($data)
    {
        return msgpack_pack($data);
    }

    private function runMethod($method, $arguments, $methodType)
    {
        $callback = $this->getMethodCallback($method, $methodType);
        switch ($methodType) {
            case MessageTypes::REQUEST:
                try {
                    $response = call_user_func_array($callback, $arguments);
                    $response = $this->createResponse(MessageTypes::RESPONSE, $response);
                } catch (\Exception $e) {
                    $response = $this->createResponse(MessageTypes::ERROR, $e);
                }
                $this->respond($response);
                break;

            case MessageTypes::ONE_WAY_CALL:
                $response = $this->createResponse(MessageTypes::ONE_WAY_CALL_ACK, null);
                $this->respond($response);
                call_user_func_array($callback, $arguments);
                break;

            case MessageTypes::PUSH:
                $self = $this;
                $ackCalled = false;
                $ack = function ($response) use ($self, $ackCalled) {
                    if ($ackCalled) {
                        return;
                    }
                    $ackCalled = true;
                    $response = $self->createResponse(MessageTypes::PUSH_ACK, $response);
                    $self->respond($response);
                };
                $arguments[] = $ack;
                try {
                    $response = call_user_func_array($callback, $arguments);
                } catch (\Exception $e) {
                    $response = $this->createResponse(MessageTypes::ERROR, $e);
                }
                $ack($response);
                break;
        }
    }


    private function getMethodCallback($method, $type)
    {
        if (!isset($this->methods[$type][$method])) {
            throw new NoSuchMethodException('There is no method "'.$method.'"');
        }
        return $this->methods[$type][$method]->getCallback();
    }
}