<?php
namespace Wookieb\ZorroRPC\Server;
use Wookieb\ZorroRPC\Exception\ServerException;
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
                throw new \InvalidArgumentException('Every element of methods list must be instance of Method');
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
                $response = $this->createResponse(MessageTypes::PONG, null, new Response(), $request);
                $this->transport->sendResponse($response);
                return;
            }
            $this->runMethodForRequest($request);
        } catch (\Exception $e) {
            $exception = new ServerException(
                'Server error: ' . $e->getMessage(),
                $this->transport->isWaitingForResponse(),
                $e
            );

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
        $callback = $this->getMethodCallback($request->getMethodName(), $methodType);

        $arguments = $this->serializer->unserializeArguments($request->getMethodName(), $request->getArgumentsBody());

        $response = new Response();
        $headers = clone $this->headers;
        $response->setHeaders($headers);

        switch ($methodType) {
            case MessageTypes::REQUEST:
                array_push($arguments, $request, $headers);
                try {
                    $result = call_user_func_array($callback, $arguments);
                    $this->createResponse(MessageTypes::RESPONSE, $result, $response, $request);
                } catch (\Exception $e) {
                    $this->createResponse(MessageTypes::ERROR, $e, $response, $request);
                }
                $this->transport->sendResponse($response);
                break;

            case MessageTypes::ONE_WAY_CALL:
                array_push($arguments, $request);
                $this->createResponse(MessageTypes::ONE_WAY_CALL_ACK, null, $response, $request);
                $this->transport->sendResponse($response);
                call_user_func_array($callback, $arguments);
                break;

            case MessageTypes::PUSH:
                array_push($arguments, $request, $headers);
                $self = $this;
                $ackCalled = false;
                $result = null;
                $ack = function ($result) use ($self, $ackCalled, $response, $request) {
                    if ($ackCalled) {
                        return;
                    }
                    $ackCalled = true;
                    $self->createResponse(MessageTypes::PUSH_ACK, $result, $response, $request);
                    $self->transport->sendResponse($response);
                };
                $arguments[] = $ack;
                try {
                    $result = call_user_func_array($callback, $arguments);
                } catch (\Exception $e) {
                    // we cannot send error message when ack was called before that moment
                    if ($ackCalled) {
                        throw $e;
                    }
                    $this->createResponse(MessageTypes::ERROR, $e, $response, $request);
                }
                $ack($result);
                break;
        }
    }

    private function getMethodCallback($method, $type)
    {
        if (!isset($this->methods[$type][$method])) {
            throw new NoSuchMethodException('There is no method "' . $method . '"');
        }
        return $this->methods[$type][$method];
    }

    private function createResponse($type, $result, Response $response, Request $request)
    {
        $response->setType($type);
        $this->forwardHeaders($request, $response);

        // response for ONE_WAY_CALL method does not contains response
        if ($type === MessageTypes::ONE_WAY_CALL_ACK || $type === MessageTypes::PONG) {
            return $response;
        }

        $requestHeaders = $request->getHeaders();
        $response->setResultBody(
            $this->serializer->serializeResult(
                $request->getMethodName(),
                $result,
                $requestHeaders ? $requestHeaders->get('Content-type') : null
            )
        );
        return $response;
    }
}