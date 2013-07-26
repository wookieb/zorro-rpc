<?php
namespace Wookieb\ZorroRPC\Tests\Transport\ZeroMQ;

use Wookieb\ZorroRPC\Exception\TransportException;
use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\Response;
use Wookieb\ZorroRPC\Transport\ZeroMQ\ZeroMQClientTransport;
use Wookieb\ZorroRPC\Exception\TimeoutException;
use Wookieb\ZorroRPC\Exception\FormatException;

class ZeroMQClientTransportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ZeroMQClientTransport
     */
    private $object;
    /**
     * @var \ZMQSocket||PHPUnit_Framework_TestCase
     */
    private $socket;

    /**
     * @var Headers
     */
    private $headers;

    protected function setUp()
    {
        $this->headers = new Headers(array(
            'custom-header' => 'value',
            'another-custom-header' => 'test'
        ));

        $this->socket = $this->getMockBuilder('\ZMQSocket')
            ->disableOriginalConstructor()
            ->getMock();
        $this->socket->expects($this->any())
            ->method('getSocketType')
            ->will($this->returnValue(\ZMQ::SOCKET_REQ));

        $this->object = new ZeroMQClientTransport($this->socket);
    }

    private function useTimeout($timeout)
    {
        $this->socket->expects($this->any())
            ->method('getSockOpt')
            ->with(\ZMQ::SOCKOPT_RCVTIMEO)
            ->will($this->returnValue($timeout * 1000));

        $this->socket->expects($this->once())
            ->method('setSockOpt')
            ->with(\ZMQ::SOCKOPT_RCVTIMEO, $timeout * 1000);
    }

    private function useRequestMessage(array $message)
    {
        $this->socket->expects($this->once())
            ->method('sendMulti')
            ->with($message);
    }

    private function useResponseMessage(array $message)
    {
        $this->socket->expects($this->once())
            ->method('recvMulti')
            ->will($this->returnValue($message));
    }

    public function testSendBasicRequest()
    {
        $message = array(MessageTypes::REQUEST, '', 'method', 'arg1', 'arg2');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::REQUEST, 'method', array('arg1', 'arg2'));
        $this->object->sendRequest($request);
    }

    public function testSendBasicRequestWithHeaders()
    {
        $message = array(MessageTypes::REQUEST, (string)$this->headers, 'method', 'arg1', 'arg2');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::REQUEST, 'method', array('arg1', 'arg2'), $this->headers);
        $this->object->sendRequest($request);
    }

    public function testSendBasicRequestWithoutArguments()
    {
        $message = array(MessageTypes::REQUEST, '', 'method');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::REQUEST, 'method');
        $this->object->sendRequest($request);
    }

    public function testSendPingRequest()
    {
        $message = array(MessageTypes::PING, '');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::PING);
        $this->object->sendRequest($request);
    }

    public function testSendPingRequestWithHeaders()
    {
        $message = array(MessageTypes::PING, (string)$this->headers);
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::PING, null, null, $this->headers);
        $this->object->sendRequest($request);
    }

    public function testSendPushRequest()
    {
        $message = array(MessageTypes::PUSH, '', 'method', 'arg1', 'arg2');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::PUSH, 'method', array('arg1', 'arg2'));
        $this->object->sendRequest($request);
    }

    public function testSendPushRequestWithHeaders()
    {
        $message = array(MessageTypes::PUSH, $this->headers, 'method', 'arg1', 'arg2');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::PUSH, 'method', array('arg1', 'arg2'), $this->headers);
        $this->object->sendRequest($request);
    }

    public function testSendPushRequestWithoutArguments()
    {
        $message = array(MessageTypes::PUSH, '', 'method');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::PUSH, 'method');
        $this->object->sendRequest($request);
    }

    public function testSendOneWayCallRequest()
    {
        $message = array(MessageTypes::ONE_WAY_CALL, '', 'method', 'arg1', 'arg2');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::ONE_WAY_CALL, 'method', array('arg1', 'arg2'));
        $this->object->sendRequest($request);
    }

    public function testSendOneWayCallRequestWithHeaders()
    {
        $message = array(MessageTypes::ONE_WAY_CALL, (string)$this->headers, 'method', 'arg1', 'arg2');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::ONE_WAY_CALL, 'method', array('arg1', 'arg2'), $this->headers);
        $this->object->sendRequest($request);
    }

    public function testSendOneWayCallRequestWithoutArguments()
    {
        $message = array(MessageTypes::ONE_WAY_CALL, '', 'method');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::ONE_WAY_CALL, 'method');
        $this->object->sendRequest($request);
    }

    public function testShouldThrowExceptionWhenZeroMQCantSendMessage()
    {
        $exception = new \ZMQSocketException('Socket exception');

        $this->socket->expects($this->once())
            ->method('sendMulti')
            ->will($this->throwException($exception));

        $this->setExpectedException('Wookieb\ZorroRPC\Exception\TransportException', 'Cannot send request');

        try {
            $request = new Request(MessageTypes::ONE_WAY_CALL, 'method');
            $this->object->sendRequest($request);
        } catch (TransportException $e) {
            $this->assertSame($exception, $e->getPrevious());
            throw $e;
        }
    }

    public function testReceivingError()
    {
        $exceptionData = 'Exception data';
        $message = array(MessageTypes::ERROR, '', $exceptionData);
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::ERROR, $exceptionData);
        $this->assertEquals($response, $this->object->receiveResponse());
    }

    public function testReceivingResponse()
    {
        $result = 'some result';
        $message = array(MessageTypes::RESPONSE, '', $result);
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::RESPONSE, $result);
        $this->assertEquals($response, $this->object->receiveResponse());
    }

    public function testReceivingResponseWithHeaders()
    {
        $result = 'some result';
        $message = array(MessageTypes::RESPONSE, (string)$this->headers, $result);
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::RESPONSE, $result, $this->headers);
        $this->assertEquals($response, $this->object->receiveResponse());
    }

    public function testReceivingPongResponse()
    {
        $message = array(MessageTypes::PONG, '');
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::PONG, null);
        $this->assertEquals($response, $this->object->receiveResponse());
    }

    public function testReceivingPongResponseWithHeaders()
    {
        $message = array(MessageTypes::PONG, (string)$this->headers);
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::PONG, null, $this->headers);
        $this->assertEquals($response, $this->object->receiveResponse());
    }

    public function testReceivingPushAckResponse()
    {
        $result = 'some ack result';
        $message = array(MessageTypes::PUSH_ACK, '', $result);
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::PUSH_ACK, $result);
        $this->assertEquals($response, $this->object->receiveResponse());
    }

    public function testReceivingPushAckResponseWithHeaders()
    {
        $result = 'some ack result';
        $message = array(MessageTypes::PUSH_ACK, (string)$this->headers, $result);
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::PUSH_ACK, $result, $this->headers);
        $this->assertEquals($response, $this->object->receiveResponse());
    }

    public function testReceivingOneWayCallAckResponse()
    {
        $message = array(MessageTypes::ONE_WAY_CALL_ACK, '');
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::ONE_WAY_CALL_ACK);
        $this->assertEquals($response, $this->object->receiveResponse());
    }

    public function testReceivingOneWayCallAckResponseWithHeaders()
    {
        $message = array(MessageTypes::ONE_WAY_CALL_ACK, (string)$this->headers);
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::ONE_WAY_CALL_ACK, null, $this->headers);
        $this->assertEquals($response, $this->object->receiveResponse());
    }

    public function testSetGetTimeout()
    {
        $timeout = 5;
        $this->useTimeout($timeout);

        $this->assertSame($this->object, $this->object->setTimeout($timeout));
        $this->assertSame($timeout, $this->object->getTimeout());
    }

    public function testTimeout()
    {
        $this->useTimeout(5);
        $this->object->setTimeout(5);
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\TimeoutException', 'Timeout (5s) reached');

        $this->socket->expects($this->once())
            ->method('recvMulti')
            ->will($this->returnValue(false));

        $this->object->receiveResponse();
    }

    public function testResponseWithoutResponseTypeIsInvalid()
    {
        $message = array();
        $this->useResponseMessage($message);
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\FormatException', 'no response type');
        $this->object->receiveResponse();
    }

    public function testResponseWithoutHeadersIsInvalid()
    {
        $message = array(MessageTypes::PONG);
        $this->useResponseMessage($message);
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\FormatException', 'no headers');
        $this->object->receiveResponse();
    }

    public function testResponseWithoutResultBodyIsInvalidWhenResponseTypeIndicateThatResultIsRequired()
    {
        $message = array(MessageTypes::PUSH_ACK, '');
        $this->useResponseMessage($message);
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\FormatException', 'no response body');
        $this->object->receiveResponse();
    }

    public function testTransportExceptionShouldBeThrowsWhenUnableToReceiveMessageFromZeroMQ()
    {
        $exception = new \ZMQSocketException('Receive error');
        $this->socket->expects($this->once())
            ->method('recvMulti')
            ->will($this->throwException($exception));

        $this->setExpectedException('Wookieb\ZorroRPC\Exception\TransportException', 'Cannot receive response');
        try {
            $this->object->receiveResponse();
        } catch (\Exception $e) {
            $this->assertSame($exception, $e->getPrevious());
            throw $e;
        }
    }

    /**
     * @test should accept only REQ sockets
     */
    public function testShouldAcceptOnlyREQSockets()
    {
        $this->socket = $this->getMockBuilder('\ZMQSocket')
            ->disableOriginalConstructor()
            ->getMock();
        $this->socket->expects($this->any())
            ->method('getSocketType')
            ->will($this->returnValue(\ZMQ::SOCKET_PUSH));

        $this->setExpectedException('\InvalidArgumentException', 'Invalid socket type');
        new ZeroMQClientTransport($this->socket);
    }
}
