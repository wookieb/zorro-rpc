<?php
namespace Wookieb\ZorroRPC\Tests\Client;
use Wookieb\ZorroRPC\Client\Client;
use Wookieb\ZorroRPC\MessageTypes;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    private $socket;

    /**
     * @var Client
     */
    private $object;

    protected function setUp()
    {
        $this->socket = $this->getMockBuilder('\ZMQSocket')
            ->disableOriginalConstructor()
            ->getMock();

        $this->socket->expects($this->once())
            ->method('getSocketType')
            ->will($this->returnValue(\ZMQ::SOCKET_REQ));

        $this->object = new Client($this->socket);
    }

    private function stubServerResponse($requestType, $method, $arguments, $responseType, $response)
    {
        $request = array($requestType);
        if ($method !== null && $arguments !== null) {
            $request[] = $method;
            $request[] = $arguments;
        }
        $request = msgpack_pack($request);

        $this->socket->expects($this->once())
            ->method('send')
            ->with($this->equalTo($request));

        $response = msgpack_pack(array(
            $responseType,
            $response
        ));

        $this->socket->expects($this->once())
            ->method('recv')
            ->will($this->returnValue($response));
    }

    public function testBasicCall()
    {
        $method = 'getUsers';
        $arguments = array(array('name' => 'woo'));

        $response = array('wookieb');
        $this->stubServerResponse(MessageTypes::REQUEST, $method, $arguments, MessageTypes::RESPONSE, $response);
        $result = $this->object->call($method, $arguments);

        $this->assertSame($response, $result);
    }

    /**
     * @test basic call should accept in response only RESPONSE message
     */
    public function testBasicCallShouldAcceptInResponseOnlyRESPONSEMessage()
    {
        $method = 'getUsers';
        $arguments = array(array('name' => 'woo'));

        $response = array('wookieb');
        $this->stubServerResponse(MessageTypes::REQUEST, $method, $arguments, MessageTypes::ONE_WAY_CALL_ACK, $response);

        $this->setExpectedException('Wookieb\ZorroRPC\Exception\FormatException', 'Unsupported message type');
        $this->object->call($method, $arguments);
    }

    public function testOneWayCall()
    {
        $method = 'activateUser';
        $arguments = array(1);

        $response = 'ok';
        $this->stubServerResponse(MessageTypes::ONE_WAY_CALL, $method, $arguments, MessageTypes::ONE_WAY_CALL_ACK, $response);
        $result = $this->object->oneWayCall($method, $arguments);
        $this->assertNotSame($response, $result);
    }

    /**
     * @test one way call should accept in response only ONE_WAY_ACK message
     */
    public function testOneWayCallShouldAcceptInResponseOnlyONE_WAY_ACKMessage()
    {
        $method = 'activateUser';
        $arguments = array(1);

        $response = 'ok';
        $this->stubServerResponse(MessageTypes::ONE_WAY_CALL, $method, $arguments, MessageTypes::RESPONSE, $response);

        $this->setExpectedException('Wookieb\ZorroRPC\Exception\FormatException', 'Unsupported message type');
        $this->object->oneWayCall($method, $arguments);
    }

    public function testPush()
    {
        $method = 'addUserToLoginQueue';
        $arguments = array(1);

        $response = 'ok';
        $this->stubServerResponse(MessageTypes::PUSH, $method, $arguments, MessageTypes::PUSH_ACK, $response);
        $result = $this->object->push($method, $arguments);
        $this->assertSame($response, $result);
    }

    /**
     * @test push should accept in response only PUSH_ACK message
     */
    public function testPushShouldAcceptInResponseOnlyPUSH_ACKMessage()
    {
        $method = 'addUserToLoginQueue';
        $arguments = array(1);

        $response = 'ok';
        $this->stubServerResponse(MessageTypes::PUSH, $method, $arguments, MessageTypes::ONE_WAY_CALL_ACK, $response);

        $this->setExpectedException('Wookieb\ZorroRPC\Exception\FormatException', 'Unsupported message type');
        $this->object->push($method, $arguments);
    }

    public function testPing()
    {
        $this->stubServerResponse(MessageTypes::PING, null, null, MessageTypes::PONG, null);
        $result = $this->object->ping();
        $this->assertTrue($result > 0);
    }

    /**
     * @test client accepts only REQ sockets
     */
    public function testClientAcceptsOnlyREQSockets()
    {
        $this->setExpectedException('\InvalidArgumentException', 'REQ required');

        $socket = $this->getMockBuilder('\ZMQSocket')
            ->disableOriginalConstructor()
            ->getMock();

        $socket->expects($this->once())
            ->method('getSocketType')
            ->will($this->returnValue(\ZMQ::SOCKET_PUSH));
        new Client($socket);
    }
}