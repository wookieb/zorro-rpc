<?php

namespace Wookieb\ZorroRPC\Tests\Serializer;

use Wookieb\ZorroRPC\Serializer\DataFormat\DataFormatInterface;
use Wookieb\ZorroRPC\Serializer\SchemalessClientSerializer;

class SchemalessClientSerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SchemalessClientSerializer
     */
    private $object;

    /**
     * @var DataFormatInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataFormat;

    protected function setUp()
    {
        $this->object = new SchemalessClientSerializer();
        $this->dataFormat = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Serializer\DataFormat\DataFormatInterface');

        $this->dataFormat->expects($this->any())
            ->method('getMimeTypes')
            ->will($this->returnValue(array('application/json')));

        $this->object->setDefaultDataFormat($this->dataFormat);
    }

    public function testShouldForwardArgumentToDataFormat()
    {
        $argument = 'some argument';
        $argumentString = 'some argument string';
        $this->dataFormat->expects($this->once())
            ->method('serialize')
            ->with($this->equalTo($argument))
            ->will($this->returnValue($argumentString));

        $result = $this->object->serializeArgument('some method', $argument);
        $this->assertSame($argumentString, $result);
    }

    public function testShouldForwardResultStringToDataFormat()
    {
        $result = array('some', 'result');
        $resultString = '[some, result]';
        $this->dataFormat->expects($this->once())
            ->method('unserialize')
            ->with($this->equalTo($resultString))
            ->will($this->returnValue($result));

        $resultData = $this->object->unserializeResult('some method', $resultString);
        $this->assertEquals($result, $resultData);
    }
}