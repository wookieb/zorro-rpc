<?php
namespace Wookieb\ZorroRPC\Client;
use Wookieb\ZorroRPC\Exception\ErrorResponseException;
use Wookieb\ZorroRPC\Exception\FormatException;
use Wookieb\ZorroRPC\Serializer\ClientSerializerInterface;
use Wookieb\ZorroRPC\Transport\ClientTransportInterface;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Response;
use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Headers\Parser;

/**
 * Implementation of ZorroRPC client
 *
 * @author <wookieb@wp.pl>
 */
class Client implements ClientInterface
{
    /**
     * @var ClientTransportInterface
     */
    private $transport;
    /**
     * @var ClientSerializerInterface
     */
    private $serializer;
    /**
     * @var Headers
     */
    private $defaultHeaders;

    private static $validResponseType = array(
        MessageTypes::REQUEST => MessageTypes::RESPONSE,
        MessageTypes::ONE_WAY_CALL => MessageTypes::ONE_WAY_CALL_ACK,
        MessageTypes::PUSH => MessageTypes::PUSH_ACK,
        MessageTypes::PING => MessageTypes::PONG
    );

    public function __construct(ClientTransportInterface $transport, ClientSerializerInterface $serializer)
    {
        $this->transport = $transport;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function setSerializer(ClientSerializerInterface $serializer)
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
    public function setDefaultHeaders(Headers $headers = null)
    {
        $this->defaultHeaders = $headers;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultHeaders()
    {
        return $this->defaultHeaders;
    }


    /**
     * {@inheritDoc}
     */
    public function call($method, array $arguments = array(), Headers $headers = null)
    {
        $request = $this->createRequest(MessageTypes::REQUEST, $method, $arguments, $headers);
        $this->send($request);
        return $this->obtainResponse($this->receive(), $request);
    }

    /**
     * {@inheritDoc}
     */
    public function oneWayCall($method, array $arguments = array(), Headers $headers = null)
    {
        $request = $this->createRequest(MessageTypes::ONE_WAY_CALL, $method, $arguments, $headers);
        $this->send($request);
        $this->obtainResponse($this->receive(), $request);
    }

    /**
     * {@inheritDoc}
     */
    public function ping()
    {
        $request = $this->createRequest(MessageTypes::PING);
        $start = microtime(true);
        $this->send($request);
        $response = $this->receive();
        $responseTime = microtime(true) - $start;
        $this->obtainResponse($response, $request);
        return $responseTime;
    }

    /**
     * {@inheritDoc}
     */
    public function push($method, array $arguments = array(), Headers $headers = null)
    {
        $request = $this->createRequest(MessageTypes::PUSH, $method, $arguments, $headers);
        $this->send($request);
        return $this->obtainResponse($this->receive(), $request);
    }

    private function createRequest($type, $method = null, $arguments = null, Headers $headers = null)
    {
        $request = new Request();
        $request->setType($type);

        $requestHeaders = $this->defaultHeaders ? clone $this->defaultHeaders : new Headers;
        if ($headers) {
            $requestHeaders->merge($headers);
        }
        $request->setHeaders($requestHeaders);

        if ($method) {
            $contentType = $requestHeaders->get('content-type');
            $arguments = $this->serializer->serializeArguments(
                $method,
                $arguments,
                $contentType
            );

            $request->setMethodName($method)
                ->setArgumentsBody($arguments);
        }
        return $request;
    }

    /**
     * @param Response $response
     * @param Request $request
     * @return mixed|null
     * @throws \Wookieb\ZorroRPC\Exception\FormatException
     */
    private function obtainResponse(Response $response, Request $request)
    {
        // woah, time to handle error
        if ($response->getType() === MessageTypes::ERROR) {
            $this->handleError($response, $request);
        }

        // check whether response type is suitable for request type
        if ($response->getType() !== self::$validResponseType[$request->getType()]) {
            $msg = sprintf(
                'Invalid response type "%s" for request type "%s"',
                MessageTypes::getName($response->getType()),
                MessageTypes::getName($request->getType())
            );
            throw new FormatException($msg, $response);
        }

        if ($response->getType() === MessageTypes::ONE_WAY_CALL_ACK ||
            $response->getType() === MessageTypes::PONG
        ) {
            return;
        }

        return $this->serializer->unserializeResult(
            $request->getMethodName(),
            $response->getResultBody(),
            $response->getHeaders()->get('Content-Type')
        );
    }

    private function handleError(Response $response, Request $request)
    {
        $responseData = $this->serializer->unserializeError(
            $request->getMethodName(),
            $response->getResultBody(),
            $response->getHeaders()->get('Content-Type')
        );

        if ($responseData instanceof \Exception) {
            throw $responseData;
        }
        if ($request->getType() === MessageTypes::PING) {
            $msg = 'Error caught during ping';
        } else {
            $msg = 'Error caught during execution of method "'.$request->getMethodName().'"';
        }

        throw new ErrorResponseException($msg, $responseData);
    }

    private function send(Request $request)
    {
        $this->transport->sendRequest($request);
    }

    private function receive()
    {
        return $this->transport->receiveResponse();
    }
}