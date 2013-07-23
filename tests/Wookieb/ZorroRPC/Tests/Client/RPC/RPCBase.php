<?php

namespace Wookieb\ZorroRPC\Tests\Client\RPC;

use Wookieb\ZorroRPC\Client\Client;
use Wookieb\ZorroRPC\Exception\TimeoutException;
use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Serializer\ClientSerializerInterface;
use Wookieb\ZorroRPC\Transport\ClientTransportInterface;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\Response;

abstract class RPCBase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected $object;
    /**
     * @var ClientSerializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $serializer;
    /**
     * @var ClientTransportInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    protected function setUp()
    {
        $this->serializer = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Serializer\ClientSerializerInterface');
        $this->transport = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Transport\ClientTransportInterface');
        $this->object = new Client($this->transport, $this->serializer);
    }

    protected function useRequest(Request $request, Headers $headers = null)
    {
        if ($headers) {
            $request = clone $request;
            $headers->merge($request->getHeaders());
            $request->setHeaders($headers);
        }

        $this->transport->expects($this->once())
            ->method('sendRequest')
            ->with($this->equalTo($request));

        if ($request->getType() !== MessageTypes::PING) {
            $contentType = $request->getHeaders()->get('content-type');
            $this->serializer->expects($this->once())
                ->method('serializeArguments')
                ->with($request->getMethodName(), $request->getArgumentsBody(), $contentType)
                ->will($this->returnValue($request->getArgumentsBody()));
        }
    }

    protected function useResponse(Request $request, Response $response, $unserializeResult = true)
    {
        $this->transport->expects($this->once())
            ->method('receiveResponse')
            ->will($this->returnValue($response));

        if ($unserializeResult && ($request->isExpectingResult() || $response->getType() === MessageTypes::ERROR)) {
            $contentType = $response->getHeaders()->get('content-type');
            $this->serializer->expects($this->once())
                ->method('unserializeResult')
                ->with($request->getMethodName(), $response->getResultBody(), $contentType)
                ->will($this->returnValue($response->getResultBody()));
        }
    }

    protected function useTimeout()
    {
        $exception = new TimeoutException('timeout occurs');
        $this->transport->expects($this->once())
            ->method('receiveResponse')
            ->will($this->throwException($exception));

        return $exception;
    }
}