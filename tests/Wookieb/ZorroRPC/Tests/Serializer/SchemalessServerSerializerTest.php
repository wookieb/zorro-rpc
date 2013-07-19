<?php

namespace Wookieb\ZorroRPC\Tests\Serializer;

use Wookieb\ZorroRPC\Serializer\DataFormat\DataFgetMimeTypesormatInterface;
use Wookieb\ZorroRPC\Serializer\SchemalessServerSerializer;

class SchemalessServerSerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SchemalessServerSerializer
     */
    private $object;

    /**
     * @var DataFormatInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataFormat;

    protected function setUp()
    {
        $this->object = new SchemalessServerSerializer();
        $this->dataFormat = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Serializer\DataFormat\DataFormatInterface');

        $this->dataFormat->expects($this->any())
            ->method('getMimeTypes')
            ->will($this->returnValue(array('application/json')));

        $this->object->setDefaultDataFormat($this->dataFormat);
    }

    public function testShouldForwardArgumentStringToDataFormat()
    {
        $argumentsString = array('some serialized argument');
        $arguments = array('unserialized argument');
        $this->dataFormat->expects($this->once())
            ->method('unserialize', $this->equalTo(null))
            ->with($this->equalTo($argumentsString[0]))
            ->will($this->returnValue($arguments[0]));

        $result = $this->object->unserializeArguments('some method', $argumentsString);
        $this->assertSame($arguments, $result);
    }

    public function testShouldForwardResultToDataFormat()
    {
        $result = array('some', 'result');
        $resultString = '[some, result]';

        $this->dataFormat->expects($this->once())
            ->method('serialize')
            ->with($this->equalTo($result))
            ->will($this->returnValue($resultString));

        $result = $this->object->serializeResult('some_method', $result);
        $this->assertSame($resultString, $result);
    }
}