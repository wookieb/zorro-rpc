<?php
namespace Wookieb\ZorroRPC\Tests\Server\RPC;

use Wookieb\ZorroRPC\Serializer\ServerSerializerInterface;
use Wookieb\ZorroRPC\Server\Server;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\Response;
use Wookieb\ZorroRPC\Transport\ServerTransportInterface;

abstract class RPCBase extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ServerSerializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $serializer;
    /**
     * @var ServerTransportInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rpcTarget;
    /**
     * @var Server
     */
    protected $object;
    /**
     * @var array
     */
    protected $methods = array();

    protected $useFalseWaitingForResponse = true;

    protected function setUp()
    {
        $this->serializer = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Serializer\ServerSerializerInterface');
        $this->transport = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Transport\ServerTransportInterface');
        if ($this->useFalseWaitingForResponse) {
            $this->transport->expects($this->any())
                ->method('isWaitingForResponse')
                ->will($this->returnValue(false));
        }


        $this->rpcTarget = $this->getMock('\stdClass', $this->methods);

        $this->object = new Server($this->transport, $this->serializer);
        $this->object->setOnErrorCallback(function ($e) {
            throw $e;
        });
    }

    protected function useRequest(Request $request, $neverUnserializeArguments = false)
    {
        $this->transport->expects($this->atLeastOnce())
            ->method('receiveRequest')
            ->will($this->returnValue($request));

        if ($request->getType() === MessageTypes::PING || $neverUnserializeArguments) {
            $this->serializer->expects($this->never())
                ->method('unserializeArguments');
        } else {
            $this->serializer->expects($this->once())
                ->method('unserializeArguments')
                ->with($request->getMethodName(), $request->getArgumentsBody(), $request->getHeaders()->get('content-type'))
                ->will($this->returnValue($request->getArgumentsBody()));
        }
    }

    protected function useResponse(Request $request, Response $response, $filter = null)
    {
        if ($request->isExpectingResult()) {
            $this->serializer->expects($this->once())
                ->method($response->getType() === MessageTypes::ERROR ? 'serializeError' : 'serializeResult')
                ->with($request->getMethodName(), $this->equalTo($response->getResultBody(), 0, 0), $response->getHeaders()->get('content-type'))
                ->will($this->returnValue($response->getResultBody()));
        } else {
            $this->serializer->expects($this->never())
                ->method('serializeResult');

        }

        if (!$filter) {
            $filter = function () {

            };
        }
        $this->transport->expects($this->once())
            ->method('sendResponse')
            ->with($response)
            ->will($this->returnCallback($filter));
    }
}
