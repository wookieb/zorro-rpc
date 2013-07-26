<?php
namespace Wookieb\ZorroRPC\Tests\Server\RPC;
require_once __DIR__.'/RPCBase.php';

use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\Response;

class BasicTest extends RPCBase
{
    protected $methods = array('basic');

    protected function setUp()
    {
        parent::setUp();
        $this->object->registerMethod('basic', array($this->rpcTarget, 'basic'));
    }

    public function testReceivingArguments()
    {
        $request = new Request(MessageTypes::REQUEST, 'basic', array(1, 2, 3));
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('basic')
            ->with(1, 2, 3, $request, $this->isInstanceOf('Wookieb\ZorroRPC\Headers\Headers'));

        $response = new Response(MessageTypes::RESPONSE);
        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testNoArguments()
    {
        $request = new Request(MessageTypes::REQUEST, 'basic');
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('basic')
            ->with($request, $this->isInstanceOf('Wookieb\ZorroRPC\Headers\Headers'));

        $response = new Response(MessageTypes::RESPONSE);
        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testReturningEmptyResult()
    {
        $request = new Request(MessageTypes::REQUEST, 'basic', array('zia'));
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('basic')
            ->with('zia', $request, $this->isInstanceOf('Wookieb\ZorroRPC\Headers\Headers'));

        $response = new Response(MessageTypes::RESPONSE, '');

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testReturningSomeResult()
    {
        $request = new Request(MessageTypes::REQUEST, 'basic', array('zia'));
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('basic')
            ->with('zia')
            ->will($this->returnValue('OK'));

        $response = new Response(MessageTypes::RESPONSE, 'OK');

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testHeadersForwarding()
    {
        $this->object->setForwardedHeaders(array('custom-header'));

        $request = new Request(MessageTypes::REQUEST, 'basic', array('zia'));
        $request->getHeaders()
            ->set('request-id', '1234567890')
            ->set('custom-header', 'some value')
            ->set('next-custom-header', 'next value');

        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('basic')
            ->with('zia')
            ->will($this->returnValue('OK'));

        $response = new Response(MessageTypes::RESPONSE, 'OK');
        $response->getHeaders()
            ->set('request-id', '1234567890')
            ->set('custom-header', 'some value');

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testArgumentsShouldBeUnserializedUsingRequestContentType()
    {
        $request = new Request(MessageTypes::REQUEST, 'basic', array('zia'));
        $request->getHeaders()
            ->set('content-type', 'application/wookieb');

        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('basic')
            ->with('zia')
            ->will($this->returnValue('OK'));

        $response = new Response(MessageTypes::RESPONSE, 'OK');

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testResultShouldBeSerializedUsingResponseContentType()
    {
        $request = new Request(MessageTypes::REQUEST, 'basic', array('zia'));
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('basic')
            ->with('zia')
            ->will($this->returnCallback(function ($arg1, $request, Headers $headers) {
                $headers->set('content-type', 'application/wookieb');
                return 'OK';
            }));

        $response = new Response(MessageTypes::RESPONSE, 'OK');
        $response->getHeaders()
            ->set('content-type', 'application/wookieb');

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testReceivingError()
    {
        $request = new Request(MessageTypes::REQUEST, 'basic', array('zia'));
        $this->useRequest($request);

        $exception = new \Exception('RPC Error');

        $this->rpcTarget->expects($this->once())
            ->method('basic')
            ->with('zia')
            ->will($this->throwException($exception));

        $response = new Response(MessageTypes::ERROR, $exception);
        $test = $this;
        $this->useResponse($request, $response, function(Response $response) use ($test) {
            $test->assertEquals(array(), $response->getResultBody()->getTrace(), 'Trace of exception must be empty');
        });;

        $this->object->handleCall();
    }

    public function testDefaultHeaders()
    {
        $defaultHeaders = new Headers(array(
            'custom-header' => 'custom header value',
            'next-custom-header' => 'sum ting wong'
        ));
        $this->object->setDefaultHeaders($defaultHeaders);

        $request = new Request(MessageTypes::REQUEST, 'basic', array('zia'));
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('basic')
            ->with('zia')
            ->will($this->returnCallback(function ($arg, $request, Headers $headers) {
                $headers->set('custom-header', 'value');
                return 'OK';
            }));


        $response = new Response(MessageTypes::RESPONSE, 'OK', new Headers(array(
            'custom-header' => 'value',
            'next-custom-header' => 'sum ting wong'
        )));

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testDefaultArguments()
    {
        $request = new Request(MessageTypes::REQUEST, 'basicWithDefaults', array('zia'));
        $this->useRequest($request);

        $test = $this;
        $this->object->registerMethod('basicWithDefaults', function ($arg1, $arg2 = 1, $arg3 = 2) use ($test) {
            $request = func_get_arg(3);
            $headers = func_get_arg(4);

            $test->assertEquals('zia', $arg1);
            $test->assertEquals(1, $arg2);
            $test->assertEquals(2, $arg3);
            $test->assertInstanceOf('Wookieb\ZorroRPC\Transport\Request', $request);
            $test->assertInstanceOf('Wookieb\ZorroRPC\Headers\Headers', $headers);

            return 'OK';
        });

        $response = new Response(MessageTypes::RESPONSE, 'OK');

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }
}
