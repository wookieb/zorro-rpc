<?php
namespace Wookieb\ZorroRPC\Tests\Server\RPC;
require_once __DIR__ . '/RPCBase.php';

use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\Response;

class RPCBasicTest extends RPCBase
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
            ->with(1, 2, 3);

        $this->object->handleCall();
    }
}
