<?php
namespace Wookieb\ZorroRPC\Tests\Server\RPC;
require_once __DIR__.'/RPCBase.php';

use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\Response;


class PingTest extends RPCBase
{
    public function testHeadersForwarding()
    {
        $this->object->setForwardedHeaders(array('custom-header'));

        $request = new Request(MessageTypes::PING);
        $request->getHeaders()
            ->set('request-id', '1234567890')
            ->set('custom-header', 'some value')
            ->set('next-custom-header', 'next value');

        $this->useRequest($request);

        $response = new Response(MessageTypes::PONG);
        $response->getHeaders()
            ->set('request-id', '1234567890')
            ->set('custom-header', 'some value');

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

        $request = new Request(MessageTypes::PING);
        $this->useRequest($request);

        $response = new Response(MessageTypes::PONG);
        $response->getHeaders()
            ->set('custom-header', 'custom header value')
            ->set('next-custom-header', 'sum ting wong');

        $this->useResponse($request, $response);
        $this->object->handleCall();
    }
}
