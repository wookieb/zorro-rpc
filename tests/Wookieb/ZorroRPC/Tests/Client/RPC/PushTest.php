<?php

namespace Wookieb\ZorroRPC\Tests\Client\RPC;
use Wookieb\ZorroRPC\Exception\ErrorResponseException;
use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\Response;

require_once __DIR__.'/RPCBase.php';

class PushTest extends RPCBase
{

    public function testReceivingResponse()
    {
        $request = new Request(MessageTypes::PUSH, 'push');
        $this->useRequest($request);

        $response = new Response(MessageTypes::PUSH_ACK, 'hehe');
        $this->useResponse($request, $response);

        $result = $this->object->push('push');
        $this->assertSame($response->getResultBody(), $result);
    }

    public function testReceivingEmptyResponse()
    {
        $request = new Request(MessageTypes::PUSH, 'push');
        $this->useRequest($request);

        $response = new Response(MessageTypes::PUSH_ACK);
        $this->useResponse($request, $response);

        $result = $this->object->push('push');
        $this->assertNull($result);
    }

    public function testReceivingError()
    {
        $request = new Request(MessageTypes::PUSH, 'push');
        $this->useRequest($request);

        $response = new Response(MessageTypes::ERROR, array('some_error'));
        $this->useResponse($request, $response);

        $msg = 'Error caught during execution of method "push"';
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\ErrorResponseException', $msg);

        try {
            $this->object->push('push');
        } catch (ErrorResponseException $e) {
            $this->assertEquals($response->getResultBody(), $e->getError());
            throw $e;
        }
    }

    public function testReceivingException()
    {
        $request = new Request(MessageTypes::PUSH, 'push');
        $this->useRequest($request);

        $exception = new \Exception('RPC Error');
        $response = new Response(MessageTypes::ERROR, $exception);
        $this->useResponse($request, $response);

        $this->setExpectedException('\Exception', 'RPC Error');
        $this->object->push('push');
    }

    public function testTimeout()
    {
        $request = new Request(MessageTypes::PUSH, 'push');
        $this->useRequest($request);

        $this->useTimeout();

        $this->setExpectedException('Wookieb\ZorroRPC\Exception\TimeoutException', 'timeout occurs');
        $this->object->push('push');
    }

    public function testReceivingFormatExceptionWhenInvalidResponseHasBeenReceived()
    {
        $request = new Request(MessageTypes::PUSH, 'push');
        $this->useRequest($request);

        $response = new Response(MessageTypes::RESPONSE);
        $this->useResponse($request, $response, false);

        $this->setExpectedException('Wookieb\ZorroRPC\Exception\FormatException', 'Invalid response type');
        $this->object->push('push');
    }

    public function testUsingDefaultHeaders()
    {
        $defaultHeaders = new Headers(array(
            'custom-header' => 'custom header value',
            'next-custom-header' => 'next custom header value'
        ));
        $this->object->setDefaultHeaders($defaultHeaders);

        $request = new Request(MessageTypes::PUSH, 'push', null, new Headers(array(
            'custom-header' => 'how much is the fish'
        )));
        $this->useRequest($request, $defaultHeaders);

        $response = new Response(MessageTypes::PUSH_ACK, 'hehe');
        $this->useResponse($request, $response);
        $this->object->push('push');
    }

    public function testSetRequestHeaders()
    {
        $headers = new Headers(array(
            'custom-headers' => 'custom header value'
        ));

        $request = new Request(MessageTypes::PUSH, 'push', null, $headers);
        $this->useRequest($request);

        $response = new Response(MessageTypes::PUSH_ACK, 'hehe');
        $this->useResponse($request, $response);
        $this->object->push('push', array(), $headers);
    }

    public function testSetRequestHeadersThatOverrideDefaultHeaders()
    {
        $headers = new Headers(array(
            'custom-header' => 'custom header value - override'
        ));

        $defaultHeaders = new Headers(array(
            'custom-header' => 'custom header value',
            'next-custom-header' => 'next custom header value'
        ));

        $this->object->setDefaultHeaders($defaultHeaders);

        $requestHeaders = clone $defaultHeaders;
        $requestHeaders->merge($headers);

        $request = new Request(MessageTypes::PUSH, 'push', null, $requestHeaders);
        $this->useRequest($request);

        $response = new Response(MessageTypes::PUSH_ACK, 'hehe');
        $this->useResponse($request, $response);
        $this->object->push('push', array(), $headers);
    }
}