<?php
namespace Wookieb\ZorroRPC\Tests\Transport\ZeroMQ;

use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\Response;
use Wookieb\ZorroRPC\Transport\ZeroMQ\ZeroMQServerTransport;
use Wookieb\ZorroRPC\Exception\FormatException;
use Wookieb\ZorroRPC\Exception\TransportException;

class ZeroMQServerTransportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \ZMQSocket|\PHPUnit_Framework_MockObject_MockObject
     */
    private $socket;
    /**
     * @var ZeroMQServerTransport
     */
    private $object;

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
            ->will($this->returnValue(\ZMQ::SOCKET_REP));

        $this->object = new ZeroMQServerTransport($this->socket);
    }

    private function useRequestMessage(array $message)
    {
        $this->socket->expects($this->once())
            ->method('recvMulti')
            ->will($this->returnValue($message));
    }

    private function useResponseMessage(array $message)
    {
        $this->socket->expects($this->once())
            ->method('sendMulti')
            ->with($message);
    }

    private function assertWaitingForResponse($to)
    {
        if ($to) {
            $msg = 'transport layer should wait for response after receiving request';
            $this->assertTrue($this->object->isWaitingForResponse(), $msg);
        } else {
            $msg = 'transport layer should not wait for response after sending one';
            $this->assertFalse($this->object->isWaitingForResponse(), $msg);
        }
    }

    public function testReceivingBasicRequest()
    {
        $message = array(MessageTypes::REQUEST, '', 'method', 'arg1', 'arg2');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::REQUEST, 'method', array('arg1', 'arg2'));
        $this->assertEquals($request, $this->object->receiveRequest());
        $this->assertWaitingForResponse(true);
    }

    public function testReceivingBasicRequestWithHeaders()
    {
        $message = array(MessageTypes::REQUEST, (string)$this->headers, 'method', 'arg1', 'arg2');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::REQUEST, 'method', array('arg1', 'arg2'), $this->headers);
        $this->assertEquals($request, $this->object->receiveRequest());
        $this->assertWaitingForResponse(true);
    }

    public function testReceivingBasicRequestWithoutArguments()
    {
        $message = array(MessageTypes::REQUEST, '', 'method');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::REQUEST, 'method');
        $this->assertEquals($request, $this->object->receiveRequest());
        $this->assertWaitingForResponse(true);
    }

    public function testReceivingPushRequest()
    {
        $message = array(MessageTypes::PUSH, '', 'method', 'arg1', 'arg2');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::PUSH, 'method', array('arg1', 'arg2'));
        $this->assertEquals($request, $this->object->receiveRequest());
        $this->assertWaitingForResponse(true);
    }

    public function testReceivingPusHRequestWithHeaders()
    {
        $message = array(MessageTypes::PUSH, (string)$this->headers, 'method', 'arg1', 'arg2');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::PUSH, 'method', array('arg1', 'arg2'), $this->headers);
        $this->assertEquals($request, $this->object->receiveRequest());
        $this->assertWaitingForResponse(true);
    }

    public function testReceivingPushRequestWithoutArguments()
    {
        $message = array(MessageTypes::PUSH, '', 'method');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::PUSH, 'method');
        $this->assertEquals($request, $this->object->receiveRequest());
        $this->assertWaitingForResponse(true);
    }

    public function testReceivingPingRequest()
    {
        $message = array(MessageTypes::PING, '');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::PING);
        $this->assertEquals($request, $this->object->receiveRequest());
        $this->assertWaitingForResponse(true);
    }

    public function testReceivingPingRequestWithHeaders()
    {
        $message = array(MessageTypes::PING, (string)$this->headers);
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::PING, null, null, $this->headers);
        $this->assertEquals($request, $this->object->receiveRequest());
        $this->assertWaitingForResponse(true);
    }

    public function testReceivingOneWayCallRequest()
    {
        $message = array(MessageTypes::ONE_WAY, '', 'method', 'arg1', 'arg2');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::ONE_WAY, 'method', array('arg1', 'arg2'));
        $this->assertEquals($request, $this->object->receiveRequest());
        $this->assertWaitingForResponse(true);
    }

    public function testReceivingOneWayCallRequestWithHeaders()
    {
        $message = array(MessageTypes::ONE_WAY, (string)$this->headers, 'method', 'arg1', 'arg2');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::ONE_WAY, 'method', array('arg1', 'arg2'), $this->headers);
        $this->assertEquals($request, $this->object->receiveRequest());
    }

    public function testReceivingOneWayCallRequestWithoutArguments()
    {
        $message = array(MessageTypes::ONE_WAY, '', 'method');
        $this->useRequestMessage($message);

        $request = new Request(MessageTypes::ONE_WAY, 'method');
        $this->assertEquals($request, $this->object->receiveRequest());
    }

    public function testThrowsExceptionWhenInvalidResponseTypeReceived()
    {
        $message = array(1000, '', 'method');
        $this->useRequestMessage($message);
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\FormatException', 'Invalid request type');
        $this->object->receiveRequest();
    }

    public function testThrowsExceptionWhenRequestDoesNotContainMethodName()
    {
        $message = array(MessageTypes::REQUEST, '');
        $this->useRequestMessage($message);
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\FormatException', 'Method name is empty');
        $this->object->receiveRequest();
    }

    public function testAcceptOnlyRepSockets()
    {
        $this->socket = $this->getMockBuilder('\ZMQSocket')
            ->disableOriginalConstructor()
            ->getMock();

        $this->socket->expects($this->any())
            ->method('getSocketType')
            ->will($this->returnValue(\ZMQ::SOCKET_PULL));

        $this->setExpectedException('\InvalidArgumentException', 'Invalid socket type');
        new ZeroMQServerTransport($this->socket);
    }

    public function testSendingErrorResponse()
    {
        $errorString = 'Some error occured';
        $message = array(MessageTypes::ERROR, '', $errorString);
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::ERROR, $errorString);
        $this->object->sendResponse($response);
        $this->assertWaitingForResponse(false);
    }

    public function testSendingResponse()
    {
        $result = 'some result';
        $message = array(MessageTypes::RESPONSE, '', $result);
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::RESPONSE, $result);
        $this->object->sendResponse($response);
        $this->assertWaitingForResponse(false);
    }

    public function testSendingResponseWithHeaders()
    {
        $result = 'some result';
        $message = array(MessageTypes::RESPONSE, (string)$this->headers, $result);
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::RESPONSE, $result, $this->headers);
        $this->object->sendResponse($response);
        $this->assertWaitingForResponse(false);
    }

    public function testSendingPongResponse()
    {
        $message = array(MessageTypes::PONG, '');
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::PONG);
        $this->object->sendResponse($response);
        $this->assertWaitingForResponse(false);
    }

    public function testSendingPongResponseWithHeaders()
    {

        $message = array(MessageTypes::PONG, (string)$this->headers);
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::PONG, null, $this->headers);
        $this->object->sendResponse($response);
        $this->assertWaitingForResponse(false);
    }

    public function testSendingPushAckResponse()
    {
        $result = 'some result';
        $message = array(MessageTypes::PUSH_ACK, '', $result);
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::PUSH_ACK, $result);
        $this->object->sendResponse($response);
        $this->assertWaitingForResponse(false);
    }

    public function testSendingPushAckResponseWithHeaders()
    {
        $result = 'some result';
        $message = array(MessageTypes::PUSH_ACK, (string)$this->headers, $result);
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::PUSH_ACK, $result, $this->headers);
        $this->object->sendResponse($response);
        $this->assertWaitingForResponse(false);
    }

    public function testSendingOneWayCallAckResponse()
    {
        $message = array(MessageTypes::ONE_WAY_ACK, '');
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::ONE_WAY_ACK);
        $this->object->sendResponse($response);
        $this->assertWaitingForResponse(false);
    }

    public function testSendingOneWayCallAckResponseWithHeaders()
    {
        $message = array(MessageTypes::ONE_WAY_ACK, (string)$this->headers);
        $this->useResponseMessage($message);

        $response = new Response(MessageTypes::ONE_WAY_ACK, null, $this->headers);
        $this->object->sendResponse($response);
        $this->assertWaitingForResponse(false);
    }

    public function testZeroMQExceptionsThrownDuringSendingResponseShouldBeConvertedToTransportException()
    {
        $exception = new \ZMQSocketException('Cannot send data');
        $this->socket->expects($this->once())
            ->method('sendMulti')
            ->will($this->throwException($exception));

        $this->setExpectedException('Wookieb\ZorroRPC\Exception\TransportException', 'Unable to send request');
        $response = new Response(MessageTypes::RESPONSE, 'some result');
        try {
            $this->object->sendResponse($response);
        } catch (TransportException $e) {
            $this->assertSame($exception, $e->getPrevious());
            throw $e;
        }
    }

    public function testZeroMQExceptionsThrownDuringReceivingRequestShouldBeConvertedToTransportException()
    {
        $exception = new \ZMQSocketException('Cannot receive data');
        $this->socket->expects($this->once())
            ->method('recvMulti')
            ->will($this->throwException($exception));

        $this->setExpectedException('Wookieb\ZorroRPC\Exception\TransportException', 'Unable to receive request');
        try {
            $this->object->receiveRequest();
        } catch (TransportException $e) {
            $this->assertSame($exception, $e->getPrevious());
            throw $e;
        }
    }
}
