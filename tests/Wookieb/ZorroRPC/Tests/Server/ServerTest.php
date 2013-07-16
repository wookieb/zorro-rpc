<?php
namespace Wookieb\ZorroRPC\Tests\Server;
use Wookieb\ZorroRPC\Transport\ServerTransportInterface;
use Wookieb\ZorroRPC\Serializer\ServerSerializerInterface;
use Wookieb\ZorroRPC\Server\Server;
use Wookieb\ZorroRPC\Server\Method;
use Wookieb\ZorroRPC\Server\MethodTypes;
use Wookieb\ZorroRPC\Headers\Headers;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServerTransportInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transport;
    /**
     * @var ServerSerializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serializer;

    /**
     * @var Server
     */
    private $server;

    protected function setUp()
    {
        $this->transport = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Transport\ServerTransportInterface');
        $this->serializer = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Serializer\ServerSerializerInterface');
        $this->server = new Server($this->transport, $this->serializer);
    }

    public function testRegisterMethodByProvidingEachArgument()
    {
        $method = new Method('rpcMethod', 'array_map', MethodTypes::ONE_WAY);

        $result = $this->server->registerMethod($method->getName(), $method->getCallback(), $method->getType());
        $this->assertSame($this->server, $result, 'Method chaining violation at "registerMethod"');
        $this->assertEquals(array($method), $this->server->getMethods());
    }

    public function testRegisterMethodByProvidingOnlyFirstArgumentWhichIsMethodObject()
    {
        $method = new Method('rpcMethod', 'array_map', MethodTypes::BASIC);

        $result = $this->server->registerMethod($method);
        $this->assertSame($this->server, $result, 'Method chaining violation at "registerMethod"');
        $this->assertEquals(array($method), $this->server->getMethods());
    }

    public function testRegisterMethods()
    {
        $method1 = new Method('rpcMethod', 'array_map', MethodTypes::BASIC);
        $method2 = new Method('rpcPush', 'array_push', MethodTypes::PUSH);

        $result = $this->server->registerMethods(array($method1, $method2));
        $this->assertSame($this->server, $result, 'Method chaining violation at "registerMethods"');
        $this->assertEquals(array($method1, $method2), $this->server->getMethods());
    }

    public function testSetForwardedHeadersPreventFromRemoveHeadersThatAlwaysNeedToBeForwarded()
    {
        $forwardedHeaders = array('my-custom-header', 'another-custom-header');
        $result = $this->server->setForwardedHeaders($forwardedHeaders);
        $this->assertSame($this->server, $result, 'Method chaining violation at "setForwardedHeaders"');

        $expected = array_merge($forwardedHeaders, array('request-id'));
        $this->assertEquals($expected, $this->server->getForwardedHeaders());
    }

    public function testSetOnErrorCallback()
    {
        $callback = 'array_slice';
        $result = $this->server->setOnErrorCallback($callback);
        $this->assertSame($this->server, $result, 'Method chaining violation at "setOnErrorCallback"');
        $this->assertSame($callback, $this->server->getOnErrorCallback());
    }

    public function testSetDefaultHeaders()
    {
        $headers = new Headers();
        $headers->set('custom-header', 'zorro rpc is awesome');
        $headers->set('another-custom-header', '2013');

        $result = $this->server->setDefaultHeaders($headers);
        $this->assertSame($this->server, $result, 'Method chaining violation at "setDefaultHeaders"');
    }
}