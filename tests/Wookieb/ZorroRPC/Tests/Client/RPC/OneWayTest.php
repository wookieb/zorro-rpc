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

class OneWayTest extends RPCBase
{
    public function testReceivingResponse()
    {
        $request = new Request(MessageTypes::ONE_WAY, 'oneWay');
        $this->useRequest($request);

        $response = new Response(MessageTypes::ONE_WAY_ACK);
        $this->useResponse($request, $response, false);

        $result = $this->object->oneWayCall('oneWay');
        $this->assertNull($result);
    }

    public function testReceivingError()
    {
        $request = new Request(MessageTypes::ONE_WAY, 'oneWay');
        $this->useRequest($request);

        $response = new Response(MessageTypes::ERROR, array('some_error'));
        $this->useResponse($request, $response);

        $msg = 'Error caught during execution of method "oneWay"';
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\ErrorResponseException', $msg);

        try {
            $this->object->oneWayCall('oneWay');
        } catch (ErrorResponseException $e) {
            $this->assertEquals($response->getResultBody(), $e->getError());
            throw $e;
        }
    }

    public function testReceivingException()
    {
        $request = new Request(MessageTypes::ONE_WAY, 'oneWay');
        $this->useRequest($request);

        $exception = new \Exception('RPC Error');
        $response = new Response(MessageTypes::ERROR, $exception);
        $this->useResponse($request, $response);

        $this->setExpectedException('\Exception', 'RPC Error');

        try {
            $this->object->oneWayCall('oneWay');
        } catch (\Exception $e) {
            $trace = $e->getTrace();
            $this->assertSame(__FILE__, $trace[0]['file'], 'Trace stack of unserialized should be changed');
            throw $e;
        }
    }

    public function testTimeout()
    {
        $request = new Request(MessageTypes::ONE_WAY, 'oneWay');
        $this->useRequest($request);

        $this->useTimeout();

        $this->setExpectedException('Wookieb\ZorroRPC\Exception\TimeoutException', 'timeout occurs');
        $this->object->oneWayCall('oneWay');
    }

    public function testReceivingFormatExceptionWhenInvalidResponseHasBeenReceived()
    {
        $request = new Request(MessageTypes::ONE_WAY, 'oneWay');
        $this->useRequest($request);

        $response = new Response(MessageTypes::RESPONSE);
        $this->useResponse($request, $response, false);

        $this->setExpectedException('Wookieb\ZorroRPC\Exception\FormatException', 'Invalid response type');
        $this->object->oneWayCall('oneWay');
    }

    public function testUsingDefaultHeaders()
    {

        $defaultHeaders = new Headers(array(
            'custom-header' => 'custom header value',
            'next-custom-header' => 'next custom header value'
        ));
        $this->object->setDefaultHeaders($defaultHeaders);

        $request = new Request(MessageTypes::ONE_WAY, 'oneWay', null, new Headers(array(
            'custom-header' => 'how much is the fish'
        )));
        $this->useRequest($request, $defaultHeaders, false);

        $response = new Response(MessageTypes::ONE_WAY_ACK);
        $this->useResponse($request, $response, false);
        $this->object->oneWayCall('oneWay');
    }

    public function testSetRequestHeaders()
    {
        $headers = new Headers(array(
            'custom-headers' => 'custom header value'
        ));

        $request = new Request(MessageTypes::ONE_WAY, 'oneWay', null, $headers);
        $this->useRequest($request);

        $response = new Response(MessageTypes::ONE_WAY_ACK);
        $this->useResponse($request, $response, false);
        $this->object->oneWayCall('oneWay', array(), $headers);
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

        $request = new Request(MessageTypes::ONE_WAY, 'oneWay', null, $requestHeaders);
        $this->useRequest($request);

        $response = new Response(MessageTypes::ONE_WAY_ACK);
        $this->useResponse($request, $response, false);
        $this->object->oneWayCall('oneWay', array(), $headers);
    }
}
