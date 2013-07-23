<?php

namespace Wookieb\ZorroRPC\Tests\Client;

use Wookieb\ZorroRPC\Client\Client;
use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Serializer\ClientSerializerInterface;
use Wookieb\ZorroRPC\Transport\ClientTransportInterface;
use Wookieb\ZorroRPC\Transport\MessageTypes;
use Wookieb\ZorroRPC\Transport\Request;
use Wookieb\ZorroRPC\Transport\Response;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $object;

    private $serializer;

    protected function setUp()
    {
        $this->serializer = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Serializer\ClientSerializerInterface');
        $transport = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Transport\ClientTransportInterface');
        $this->object = new Client($transport, $this->serializer);
    }

    public function testSetDefaultHeaders()
    {
        $headers = new Headers(array(
            'some-header' => 'headerValue'
        ));

        $result = $this->object->setDefaultHeaders($headers);
        $this->assertSame($this->object, $result, 'Method chaining violation at "setDefaultHeaders"');
        $this->assertSame($headers, $this->object->getDefaultHeaders());
    }

    public function testShouldGiveAccessToSerializer()
    {
        $this->assertSame($this->serializer, $this->object->getSerializer());
    }

    public function testShouldBeAbleToChangeSerializer() {
        $newSerializer = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Serializer\ClientSerializerInterface');
        $result = $this->object->setSerializer($newSerializer);
        $this->assertSame($this->object, $result, 'method chaining violation at "setSerializer"');
        $this->assertSame($newSerializer, $this->object->getSerializer());
    }
}
