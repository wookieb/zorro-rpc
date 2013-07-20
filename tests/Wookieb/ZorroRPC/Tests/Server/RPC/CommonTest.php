<?php

namespace Wookieb\ZorroRPC\Tests\Server\RPC;

use Wookieb\ZorroRPC\Exception\NoSuchMethodException;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\Response;

class CommonTest extends RPCBase
{
    protected $useFalseWaitingForResponse = false;

    public function testShouldReturnErrorWhenInvokedMethodDoesNotExists()
    {
        $request = new Request(MessageTypes::REQUEST, 'run', array(1, 2, 3));
        $this->useRequest($request, true);

        $this->transport->expects($this->any())
            ->method('isWaitingForResponse')
            ->will($this->returnValue(true));

        $exception = new NoSuchMethodException('There is no method "run"');
        $response = new Response(MessageTypes::ERROR, $exception);
        $this->useResponse(new Request(MessageTypes::REQUEST, ''), $response);
        $this->object->handleCall();
    }
}