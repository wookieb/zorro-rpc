<?php
namespace Wookieb\ZorroRPC\Tests\Server\RPC;

use Wookieb\ZorroRPC\Serializer\ServerSerializerInterface;
use Wookieb\ZorroRPC\Server\Server;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\Response;
use Wookieb\ZorroRPC\Transport\ServerTransportInterface;

class RPCBase extends \PHPUnit_Framework_TestCase
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

    protected function setUp()
    {
        $this->serializer = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Serializer\ServerSerializerInterface');
        $this->transport = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Transport\ServerTransportInterface');
        $this->rpcTarget = $this->getMock('\stdClass', $this->methods);

        $this->object = new Server($this->transport, $this->serializer);
    }

    protected function useRequest(Request $request)
    {
        $this->transport->expects($this->atLeastOnce())
            ->method('receiveRequest')
            ->will($this->returnValue($request));

        foreach ($request->getArgumentsBody() as $key => $argument) {
            $this->serializer->expects($this->at($key))
                ->method('unserializeArgument')
                ->with($request->getMethodName(), $argument)
                ->will($this->returnValue($argument));
        }
    }

    protected function useResponse(Response $response)
    {

    }
}
