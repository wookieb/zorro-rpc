<?php
namespace Wookieb\ZorroRPC\Server;
use Wookieb\ZorroRPC\Exception\ExceptionChanger;
use Wookieb\ZorroRPC\Serializer\ServerSerializerInterface;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\ServerTransportInterface;
use Wookieb\ZorroRPC\Transport\Response;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Exception\NoSuchMethodException;
use Wookieb\ZorroRPC\Headers\Headers;

/**
 * Basic implementation of ZorroRPC server
 *
 * @author wookieb <wookieb@wp.pl>
 */
class Server implements ServerInterface
{
    private $methods = array();

    /**
     * @var ServerSerializerInterface
     */
    private $serializer;

    /**
     * @var \Wookieb\ZorroRPC\Transport\ServerTransportInterface
     */
    private $transport;

    /**
     * @var \Wookieb\ZorroRPC\Headers\Headers
     */
    private $headers;

    /**
     * List of names of headers that should be forwarded to response always when they exists in response
     *
     * @var array
     */
    private $forwardedHeaders = array();

    /**
     * @var callback
     */
    private $onErrorCallback;

    /**
     * List of names of headers that MUST be forwarded regardless of list of forwarded headers provided by user
     *
     * @var array
     */
    private static $requiredForwardedHeaders = array('request-id');

    private static $messageTypeToMethodType = array(
        MessageTypes::ONE_WAY_CALL => MethodTypes::ONE_WAY,
        MessageTypes::REQUEST => MethodTypes::BASIC,
        MessageTypes::PUSH => MethodTypes::PUSH
    );

    public function __construct(ServerTransportInterface $transport, ServerSerializerInterface $serializer)
    {
        $this->transport = $transport;
        $this->setSerializer($serializer);

        $this->headers = new Headers();
    }

    /**
     * {@inheritDoc}
     */
    public function setSerializer(ServerSerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultHeaders(Headers $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setForwardedHeaders(array $headers)
    {
        $this->forwardedHeaders = array_merge($headers, self::$requiredForwardedHeaders);
        $this->forwardedHeaders = array_unique($this->forwardedHeaders);
        return $this;
    }

    /**
     * @return array
     */
    public function getForwardedHeaders()
    {
        return $this->forwardedHeaders;
    }

    /**
     * {@inheritDoc}
     */
    public function setOnErrorCallback($callback)
    {
        if (!is_callable($callback, true)) {
            throw new \InvalidArgumentException('Argument must be a callback');
        }
        $this->onErrorCallback = $callback;
        return $this;
    }

    /**
     * @return callable
     */
    public function getOnErrorCallback()
    {
        return $this->onErrorCallback;
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
                throw new \InvalidArgumentException('Every element of list must be instance of Method');
            }
            $this->registerMethod($method);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethods()
    {
        return array_values(call_user_func_array('array_merge', $this->methods));
    }

    /**
     * {@inheritDoc}
     */
    public function run()
    {
        while (true) {
            $this->handleCall();
        }
    }

    public function handleCall()
    {
        try {
            $request = $this->transport->receiveRequest();
            if ($request->getType() === MessageTypes::PING) {
                $response = $this->createResponse(
                    MessageTypes::PONG,
                    null,
                    new Response(null, null, clone $this->headers),
                    $request
                );

                $this->transport->sendResponse($response);
                return;
            }
            $this->runMethodForRequest($request);
        } catch (\Exception $exception) {
            if ($this->transport->isWaitingForResponse()) {
                $request = new Request();
                $request->setMethodName('')
                    ->setType(MessageTypes::REQUEST);

                $response = new Response();
                $this->createResponse(MessageTypes::ERROR, $exception, $response, $request);

                $this->transport->sendResponse($response);
            } else if ($this->onErrorCallback) {
                call_user_func($this->onErrorCallback, $exception);
            }
        }
    }

    private function forwardHeaders(Request $request, Response $response)
    {
        $requestHeaders = $request->getHeaders();
        $responseHeaders = $response->getHeaders();
        foreach ($this->forwardedHeaders as $header) {
            $headerValue = $requestHeaders->get($header);
            if ($headerValue) {
                $responseHeaders->set($header, $headerValue);
            }
        }
        return $response;
    }

    private function runMethodForRequest(Request $request)
    {
        $methodType = self::$messageTypeToMethodType[$request->getType()];
        $method = $this->getMethod($request->getMethodName(), $methodType);

        $arguments = $this->serializer->unserializeArguments(
            $request->getMethodName(),
            $request->getArgumentsBody(),
            $request->getHeaders()->get('content-type')
        );

        $response = new Response();
        $headers = clone $this->headers;
        $response->setHeaders($headers);

        switch ($methodType) {
            case MethodTypes::BASIC:
                try {
                    $result = $method->call($arguments, $request, $headers);
                    $this->createResponse(MessageTypes::RESPONSE, $result, $response, $request);
                } catch (\Exception $e) {
                    $this->createResponse(MessageTypes::ERROR, $e, $response, $request);
                }
                $this->transport->sendResponse($response);
                break;

            case MethodTypes::ONE_WAY:
                $this->createResponse(MessageTypes::ONE_WAY_CALL_ACK, null, $response, $request);
                $this->transport->sendResponse($response);
                $method->call($arguments, $request);
                break;

            case MethodTypes::PUSH:
                $self = $this;
                $ackCalled = false;
                $result = null;
                $transport = $this->transport;

                // hack for php 5.3 which makes "createResponse" accessible from closure
                $create = new \ReflectionMethod($this, 'createResponse');
                $create->setAccessible(true);

                $ack = function ($result = null) use ($self, &$ackCalled, $response, $request, $transport, $create) {
                    if ($ackCalled) {
                        return;
                    }
                    $ackCalled = true;
                    $create->invoke($self, MessageTypes::PUSH_ACK, $result, $response, $request);
                    $transport->sendResponse($response);
                };
                try {
                    $result = $method->call($arguments, $request, $headers, $ack);
                    $ack($result);
                } catch (\Exception $e) {
                    // we cannot send error message when ack was called before that moment
                    if ($ackCalled) {
                        throw $e;
                    }
                    $ackCalled = true;
                    $this->createResponse(MessageTypes::ERROR, $e, $response, $request);
                    $self->transport->sendResponse($response);
                }

                break;
        }
    }

    /**
     * @param string $method method name
     * @param integer $type method type
     * @return Method
     * @throws \Wookieb\ZorroRPC\Exception\NoSuchMethodException
     */
    private function getMethod($method, $type)
    {
        if (!isset($this->methods[$type][$method])) {
            throw new NoSuchMethodException('There is no method "'.$method.'"');
        }
        return $this->methods[$type][$method];
    }

    private function createResponse($type, $result, Response $response, Request $request)
    {
        $response->setType($type);
        $this->forwardHeaders($request, $response);

        if ($type === MessageTypes::ONE_WAY_CALL_ACK || $type === MessageTypes::PONG) {
            return $response;
        }

        if ($type === MessageTypes::ERROR) {
            if ($result instanceof \Exception) {
                ExceptionChanger::clean($result);
            }
            $resultBody = $this->serializer->serializeError(
                $request->getMethodName(),
                $result,
                $response->getHeaders()->get('content-type')
            );
        } else {
            $resultBody = $this->serializer->serializeResult(
                $request->getMethodName(),
                $result,
                $response->getHeaders()->get('content-type')
            );
        }
        $response->setResultBody($resultBody);
        return $response;
    }
}