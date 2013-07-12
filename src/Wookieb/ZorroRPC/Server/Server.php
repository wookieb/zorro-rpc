<?php
namespace Wookieb\ZorroRPC\Server;
use Psr\Log\LoggerAwareInterface;
use Wookieb\ZorroRPC\Exception\ServerException;
use Wookieb\ZorroRPC\Serializer\ServerSerializerInterface;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\ServerTransportInterface;
use Wookieb\ZorroRPC\Transport\Response;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Exception\NoSuchMethodException;
use Wookieb\ZorroRPC\Headers\Headers;
use Psr\Log\LoggerInterface;

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

    private $headers;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private static $forwardedHeaders = array(
        'RequestId'
    );

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
     * @return self
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
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
    function setDefaultHeaders(Headers $headers)
    {
        $this->headers = $headers;
        return $this;
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
            $this->handleCall();
        }
    }

    public function handleCall()
    {
        try {
            $request = $this->transport->receiveRequest();
            if ($request->getType() === MessageTypes::PING) {
                $response = new Response(MessageTypes::PONG);
                $this->forwardHeaders($request, $response);
                $this->transport->sendResponse(new Response(MessageTypes::PONG));
                return;
            }
            $this->runMethodForRequest($request);
        } catch (\Exception $e) {

            $exception = new ServerException(
                'Server error: '.$e->getMessage(),
                $this->transport->isWaitingForResponse(),
                $e
            );

            if ($this->logger) {
                $this->logger->error($exception);
            }

            if ($this->transport->isWaitingForResponse()) {
                $request = new Request();
                $request->setMethodName('')
                    ->setType(MessageTypes::REQUEST);

                $response = new Response();
                $this->createResponse(MessageTypes::ERROR, $exception, $response, $request);

                $this->transport->sendResponse($response);
            }

        }
    }

    private function runMethodForRequest(Request $request)
    {
        $methodType = self::$messageTypeToMethodType[$request->getType()];
        $callback = $this->getMethodCallback($request->getMethodName(), $methodType);

        $arguments = $this->serializer->unserializeArguments(
            $request->getMethodName(),
            $request->getArguments(),
            $request->getHeaders()->get('Content-type')
        );

        $response = new Response();
        $response->setHeaders(clone $this->headers);

        switch ($methodType) {
            case MessageTypes::REQUEST:
                array_push($arguments, $request, $response->getHeaders());
                try {
                    $result = call_user_func_array($callback, $arguments);
                    $this->createResponse(MessageTypes::RESPONSE, $result, $response, $request);
                } catch (\Exception $e) {
                    $this->createResponse(MessageTypes::ERROR, $e, $response, $request);
                }
                $this->transport->sendResponse($response);
                break;

            case MessageTypes::ONE_WAY_CALL:
                $this->createResponse(MessageTypes::ONE_WAY_CALL_ACK, null, $response, $request);
                $this->transport->sendResponse($response);
                call_user_func_array($callback, $arguments);

                break;

            // TODO
            case MessageTypes::PUSH:
                array_push($arguments, $request, $response->getHeaders());
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
            throw new NoSuchMethodException('There is no method "'.$method.'"');
        }
        return $this->methods[$type][$method]->getCallback();
    }

    private function createResponse($type, $result, Response $response, Request $request = null)
    {
        $response->setType($type);
        $this->forwardHeaders($request, $response);
        if ($type === MessageTypes::ONE_WAY_CALL_ACK) {
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

    private function forwardHeaders(Request $request, Response $response)
    {
        $headers = $request->getHeaders();
        $headersToForward = array();
        if ($headers) {
            foreach (self::$forwardedHeaders as $header) {
                if ($headers->has($header)) {
                    $headersToForward[$header] = $headers->get($header);
                }
            }
        }

        if ($headersToForward !== array()) {
            $tmpHeaders = $response->getHeaders() ? $response->getHeaders()->getAll() : array();
            $response->setHeaders(
                new Headers(array_merge($tmpHeaders, $headersToForward))
            );
        }
    }
}