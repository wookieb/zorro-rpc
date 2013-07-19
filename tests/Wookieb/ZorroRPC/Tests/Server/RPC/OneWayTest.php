<?php

namespace Wookieb\ZorroRPC\Tests\Server\RPC;
use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Server\MethodTypes;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\Response;

require_once __DIR__ . '/RPCBase.php';

class OneWayTest extends RPCBase
{
    protected $methods = array('owc');

    protected function setUp()
    {
        parent::setUp();
        $this->object->registerMethod('owc', array($this->rpcTarget, 'owc'), MethodTypes::ONE_WAY);
    }

    public function testDefaultHeaders()
    {
        $defaultHeaders = new Headers(array(
            'custom-header' => 'custom header value',
            'next-custom-header' => 'sum ting wong'
        ));
        $this->object->setDefaultHeaders($defaultHeaders);

        $request = new Request(MessageTypes::ONE_WAY_CALL, 'owc', array('zia'));
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('owc')
            ->with('zia')
            ->will($this->returnCallback(function ($arg, Request $request, Headers $headers) {
                return 'OK';
            }));

        $response = new Response(MessageTypes::ONE_WAY_CALL_ACK, null, new Headers(array(
            'custom-header' => 'custom header value',
            'next-custom-header' => 'sum ting wong'
        )));

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testHeadersForwarding()
    {
        {
            $this->object->setForwardedHeaders(array('custom-header'));

            $request = new Request(MessageTypes::ONE_WAY_CALL, 'owc', array('zia'));
            $request->getHeaders()
                ->set('request-id', '1234567890')
                ->set('custom-header', 'some value')
                ->set('next-custom-header', 'next value');

            $this->useRequest($request);

            $this->rpcTarget->expects($this->once())
                ->method('owc')
                ->with('zia')
                ->will($this->returnValue('OK'));

            $response = new Response(MessageTypes::ONE_WAY_CALL_ACK);
            $response->getHeaders()
                ->set('request-id', '1234567890')
                ->set('custom-header', 'some value');

            $this->useResponse($request, $response);
            $this->object->handleCall();
        }
    }

    public function testReceivingArguments()
    {
        $request = new Request(MessageTypes::ONE_WAY_CALL, 'owc', array(1));
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('owc')
            ->will($this->returnCallback(function () {
                $this->assertSame(1, func_get_arg(0));
                $this->assertInstanceOf('Wookieb\ZorroRPC\Transport\Request', func_get_arg(1));
                $this->assertSame(null, func_get_arg(2));
            }));

        $response = new Response(MessageTypes::ONE_WAY_CALL_ACK);

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testArgumentsShouldBeUnserializedUsingRequestContentType()
    {
        $request = new Request(MessageTypes::ONE_WAY_CALL, 'owc', array('zia'));
        $request->getHeaders()
            ->set('content-type', 'application/wookieb');

        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('owc')
            ->with('zia')
            ->will($this->returnValue('OK'));

        $response = new Response(MessageTypes::ONE_WAY_CALL_ACK);

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testDefaultArguments()
    {
        $request = new Request(MessageTypes::ONE_WAY_CALL, 'owcWithDefaults', array('zia'));
        $this->useRequest($request);

        $test = $this;
        $this->object->registerMethod('owcWithDefaults', function ($arg1, $arg2 = 1, $arg3 = 2) use ($test) {
            $request = func_get_arg(4);
            $headers = func_get_arg(5);

            $test->assertEquals('zia', $arg1);
            $test->assertEquals(1, $arg2);
            $test->assertEquals(2, $arg3);
            $test->assertInstanceOf('Wookieb\ZorroRPC\Transport\Request', $request);
            $test->assertNull($headers);

            return 'OK';
        }, MethodTypes::ONE_WAY);

        $response = new Response(MessageTypes::ONE_WAY_CALL_ACK);

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }

    public function testNoArguments()
    {
        $request = new Request(MessageTypes::ONE_WAY_CALL, 'owc');
        $this->useRequest($request);

        $this->rpcTarget->expects($this->once())
            ->method('owc')
            ->with($this->isInstanceOf('Wookieb\ZorroRPC\Transport\Request'))
            ->will($this->returnValue('OK'));

        $response = new Response(MessageTypes::ONE_WAY_CALL_ACK);
        $this->useResponse($request, $response);
        $this->object->handleCall();
    }
}