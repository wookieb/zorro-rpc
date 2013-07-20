<?php

namespace Wookieb\ZorroRPC\Tests\Server\RPC;
require_once __DIR__.'/RPCBase.php';

use Wookieb\ZorroRPC\Server\MethodTypes;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Transport\Response;

class PushTest extends RPCBase
{
    protected $methods = array('push');

    protected function setUp()
    {
        parent::setUp();
        $this->object->registerMethod('push', array($this->rpcTarget, 'push'), MethodTypes::PUSH);
    }

    public function testReceivingArguments()
    {
        $request = new Request(MessageTypes::PUSH, 'push', array(1));
        $this->useRequest($request);

        $test = $this;
        $this->rpcTarget->expects($this->once())
            ->method('push')
            ->will($this->returnCallback(function () use ($test) {
                $test->assertSame(1, func_get_arg(0));
                $test->assertInstanceOf('\Closure', func_get_arg(1));
                $test->assertInstanceOf('Wookieb\ZorroRPC\Transport\Request', func_get_arg(2));
                $test->assertInstanceOf('Wookieb\ZorroRPC\Headers\Headers', func_get_arg(3));
            }));

        $response = new Response(MessageTypes::PUSH_ACK, '');

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testReturningResultByUsingCallback()
    {
        $request = new Request(MessageTypes::PUSH, 'push', array(1));
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('push')
            ->will($this->returnCallback(function ($arg, $callback) {
                $callback('OK');
            }));

        $response = new Response(MessageTypes::PUSH_ACK, 'OK');

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testReturningResultByUsingReturn()
    {
        $request = new Request(MessageTypes::PUSH, 'push', array(1));
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('push')
            ->will($this->returnValue('OK'));

        $response = new Response(MessageTypes::PUSH_ACK, 'OK');

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testReturningEmptyResultByUsingCallback()
    {
        $request = new Request(MessageTypes::PUSH, 'push', array(1));
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('push')
            ->will($this->returnCallback(function ($arg, \Closure $callback) {
                $callback();
            }));

        $response = new Response(MessageTypes::PUSH_ACK);

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testReceivingError()
    {
        $request = new Request(MessageTypes::PUSH, 'push', array('zia'));
        $this->useRequest($request);

        $exception = new \Exception('RPC Error');

        $this->rpcTarget->expects($this->once())
            ->method('push')
            ->with('zia')
            ->will($this->throwException($exception));

        $response = new Response(MessageTypes::ERROR, $exception);
        $this->useResponse($request, $response);

        $this->object->handleCall();
    }

    public function testThrowingErrorAfterReturningResultWillBeTreatedAsInternalServerError()
    {
        $request = new Request(MessageTypes::PUSH, 'push', array('zia'));
        $this->useRequest($request);

        $exception = new \Exception('RPC Error');

        $this->rpcTarget->expects($this->once())
            ->method('push')
            ->with('zia')
            ->will($this->returnCallback(function ($arg, $callback) use ($exception) {
                $callback();
                throw $exception;
            }));

        $test = $this;
        $errorCatched = false;
        $this->object->setOnErrorCallback(function (\Exception $error) use ($exception, $test, &$errorCatched) {
            $test->assertEquals($exception->getMessage(), $error->getMessage());
            $errorCatched = true;
        });

        $response = new Response(MessageTypes::PUSH_ACK, '');
        $this->useResponse($request, $response);

        $this->object->handleCall();
        $this->assertTrue($errorCatched);
    }

    public function testHeadersForwarding()
    {
        {
            $this->object->setForwardedHeaders(array('custom-header'));

            $request = new Request(MessageTypes::PUSH, 'push', array('zia'));
            $request->getHeaders()
                ->set('request-id', '1234567890')
                ->set('custom-header', 'some value')
                ->set('next-custom-header', 'next value');

            $this->useRequest($request);

            $this->rpcTarget->expects($this->once())
                ->method('push')
                ->with('zia')
                ->will($this->returnValue('OK'));

            $response = new Response(MessageTypes::PUSH_ACK, 'OK');
            $response->getHeaders()
                ->set('request-id', '1234567890')
                ->set('custom-header', 'some value');

            $this->useResponse($request, $response);
            $this->object->handleCall();
        }
    }

    public function testNoArguments()
    {
        $request = new Request(MessageTypes::PUSH, 'push');
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('push')
            ->with($this->isInstanceOf('\Closure'))
            ->will($this->returnValue('OK'));

        $response = new Response(MessageTypes::PUSH_ACK, 'OK');
        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testDefaultHeaders()
    {
        $defaultHeaders = new Headers(array(
            'custom-header' => 'custom header value',
            'next-custom-header' => 'sum ting wong'
        ));
        $this->object->setDefaultHeaders($defaultHeaders);

        $request = new Request(MessageTypes::PUSH, 'push', array('zia'));
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('push')
            ->with('zia')
            ->will($this->returnCallback(function ($arg, $callback, Request $request, Headers $headers) {
                $headers->set('custom-header', 'value');
                return 'OK';
            }));


        $response = new Response(MessageTypes::PUSH_ACK, 'OK', new Headers(array(
            'custom-header' => 'value',
            'next-custom-header' => 'sum ting wong'
        )));

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testArgumentsShouldBeUnserializedUsingRequestContentType()
    {
        $request = new Request(MessageTypes::PUSH, 'push', array('zia'));
        $request->getHeaders()
            ->set('content-type', 'application/wookieb');

        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('push')
            ->with('zia')
            ->will($this->returnValue('OK'));

        $response = new Response(MessageTypes::PUSH_ACK, 'OK');

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testResultShouldBeSerializedUsingResponseContentType()
    {
        $request = new Request(MessageTypes::PUSH, 'push', array('zia'));
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('push')
            ->with('zia')
            ->will($this->returnCallback(function ($arg1, $callback, Request $request, Headers $headers) {
                $headers->set('content-type', 'application/wookieb');
                return 'OK';
            }));

        $response = new Response(MessageTypes::PUSH_ACK, 'OK');
        $response->getHeaders()
            ->set('content-type', 'application/wookieb');

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testDefaultArguments()
    {
        $request = new Request(MessageTypes::PUSH, 'pushWithDefaults', array('zia'));
        $this->useRequest($request);

        $test = $this;
        $this->object->registerMethod('pushWithDefaults', function ($arg1, $arg2 = 1, $arg3 = 2) use ($test) {
            $request = func_get_arg(4);
            $headers = func_get_arg(5);

            $test->assertEquals('zia', $arg1);
            $test->assertEquals(1, $arg2);
            $test->assertEquals(2, $arg3);
            $test->assertInstanceOf('Wookieb\ZorroRPC\Transport\Request', $request);
            $test->assertInstanceOf('Wookieb\ZorroRPC\Headers\Headers', $headers);

            return 'OK';
        }, MethodTypes::PUSH);

        $response = new Response(MessageTypes::PUSH_ACK, 'OK');

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testWhenCallbackIsCalledThenResponseShouldBeSendBeforeEndOfRpcMethod()
    {
        $request = new Request(MessageTypes::PUSH, 'push', array('zia'));
        $this->useRequest($request);

        $responseSent = false;
        $this->transport->expects($this->once())
            ->method('sendResponse')
            ->will($this->returnCallback(function () use (&$responseSent) {
                $responseSent = true;
            }));

        $test = $this;
        $this->object->registerMethod('push', function ($arg, \Closure $callback) use ($test, &$responseSent) {
            $callback('OK');
            $test->assertTrue($responseSent, 'response should be send earlier');
        }, MethodTypes::PUSH);

        $response = new Response(MessageTypes::PUSH_ACK, 'OK');

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }
}