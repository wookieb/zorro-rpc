<?php

namespace Wookieb\ZorroRPC\Tests\Client\RPC;
require_once __DIR__.'/RPCBase.php';
use Wookieb\ZorroRPC\Exception\FormatException;
use Wookieb\ZorroRPC\Exception\TimeoutException;
use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\Response;
use Wookieb\ZorroRPC\Exception\ErrorResponseException;

class PingTest extends RPCBase
{
    public function testReceivingResponse()
    {
        $request = new Request(MessageTypes::PING);
        $this->useRequest($request);

        $response = new Response(MessageTypes::PONG);
        $this->useResponse($request, $response);

        $time = $this->object->ping();
        $this->assertTrue($time > 0);
    }

    public function testReceivingError()
    {
        $request = new Request(MessageTypes::PING);
        $this->useRequest($request);

        $response = new Response(MessageTypes::ERROR, array('some_error'));
        $this->useResponse($request, $response);

        $msg = 'Error caught during ping';
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\ErrorResponseException', $msg);

        try {
            $this->object->ping();
        } catch (ErrorResponseException $e) {
            $this->assertEquals($response->getResultBody(), $e->getError());
            throw $e;
        }
    }

    public function testReceivingException()
    {
        $request = new Request(MessageTypes::PING);
        $this->useRequest($request);

        $exception = new \Exception('RPC Error');
        $response = new Response(MessageTypes::ERROR, $exception);
        $this->useResponse($request, $response);

        $this->setExpectedException('\Exception', 'RPC Error');

        $this->object->ping();
    }

    public function testTimeout()
    {
        $request = new Request(MessageTypes::PING);
        $this->useRequest($request);
        $this->useTimeout();
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\TimeoutException', 'timeout occurs');
        $this->object->ping();
    }

    public function testReceivingFormatExceptionWhenInvalidResponseHasBeenReceived()
    {
        $request = new Request(MessageTypes::PING);
        $this->useRequest($request);

        $response = new Response(MessageTypes::RESPONSE, 'hehe');
        $this->useResponse($request, $response, false);

        $this->setExpectedException('Wookieb\ZorroRPC\Exception\FormatException', 'Invalid response type');
        $this->object->ping();
    }

    public function testUsingDefaultHeaders()
    {
        $defaultHeaders = new Headers(array(
            'custom-header' => 'custom header value',
            'next-custom-header' => 'next custom header value'
        ));
        $this->object->setDefaultHeaders($defaultHeaders);

        $request = new Request(MessageTypes::PING);
        $request->setHeaders($defaultHeaders);
        $this->useRequest($request);

        $response = new Response(MessageTypes::PONG);
        $this->useResponse($request, $response);
        $this->object->ping();
    }
}