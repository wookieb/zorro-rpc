<?php
namespace Wookieb\ZorroRPC\Tests\Serializer;
use Wookieb\ZorroRPC\Serializer\AbstractSerializer;
use Wookieb\ZorroRPC\Serializer\DataFormat\DataFormatInterface;
use Wookieb\ZorroRPC\Exception\DataFormatNotFoundException;

class AbstractSerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractSerializer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $object;

    protected function setUp()
    {
        $this->object = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Serializer\AbstractSerializer');
    }

    private function createDataFormat(array $mimeTypes = null)
    {
        $mock = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Serializer\DataFormat\DataFormatInterface');

        $mimeTypes = $mimeTypes ? $mimeTypes : array('application/json');
        $mock->expects($this->any())
            ->method('getMimeTypes')
            ->will($this->returnValue($mimeTypes));

        return $mock;
    }

    public function testShouldBeAbleToSetDefaultDataFormatFromConstructor() {
        $dataFormat = $this->createDataFormat(array('application/wookieb'));
        $this->object = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Serializer\AbstractSerializer', array($dataFormat));
        $this->assertSame($dataFormat, $this->object->getDefaultDataFormat());
    }

    public function testRegisterDataFormat()
    {
        $dataFormat = $this->createDataFormat(array(
            'text/xml'
        ));
        $result = $this->object->registerDataFormat($dataFormat);
        $this->assertSame($this->object, $result, 'Method chaining violation at "registerDataFormat"');
        $this->assertSame($dataFormat, $this->object->getDataFormatForMimeType('text/xml'));
    }

    public function testRegisterDataFormatShouldThrowExceptionWhenDataFormatDoesNotDefineMimeTypes()
    {
        $dataFormat = $this->getMockForAbstractClass('Wookieb\ZorroRPC\Serializer\DataFormat\DataFormatInterface');
        $dataFormat->expects($this->any())
            ->method('getMimeTypes')
            ->will($this->returnValue(null));

        $this->setExpectedException('\UnexpectedValueException', 'must returns array of supported mime types');
        $this->object->registerDataFormat($dataFormat);
    }

    public function testRegisterDataFormatShouldThrowExceptionWhenDataFormatNotFoundForGivenDataFormat()
    {
        $msg = 'No data format defined for mime type "application/msgpack"';
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\DataFormatNotFoundException', $msg);
        $this->object->getDataFormatForMimeType('application/msgpack');
    }

    /**
     * @test registerDataFormat should throw exception when default data format is not defined adn mimeType is NULL
     */
    public function testRegisterDataFormatShouldThrowExceptionWhenNoDefaultDataFormat()
    {
        $msg = 'Default data format not defined';
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\DataFormatNotFoundException', $msg);

        $this->object->getDataFormatForMimeType();
    }

    public function testAtTheBeginningDefaultDataFormatShouldBeNull()
    {
        $this->assertNull($this->object->getDefaultDataFormat());
    }

    public function testAtTheBeginningDefaultMimeTypeShouldBeNull()
    {
        $this->assertNull($this->object->getDefaultMimeType());
    }

    public function testSetDefaultDataFormat()
    {
        $dataFormat = $this->createDataFormat();
        $result = $this->object->setDefaultDataFormat($dataFormat);

        $this->assertSame($this->object, $result, 'Method chaining violation at "setDefaultDataFormat"');
        $this->assertSame($dataFormat, $this->object->getDefaultDataFormat());
        $this->assertSame('application/json', $this->object->getDefaultMimeType());
    }
}