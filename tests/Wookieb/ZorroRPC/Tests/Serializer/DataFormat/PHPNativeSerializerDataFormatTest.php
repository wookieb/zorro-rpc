<?php
namespace Wookieb\ZorroRPC\Tests\Serializer\DataFormat;

use Wookieb\ZorroRPC\Serializer\DataFormat\PHPNativeSerializerDataFormat;

class PHPNativeSerializerDataFormatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PHPNativeSerializerDataFormat
     */
    private $object;

    protected function setUp()
    {
        $this->object = new PHPNativeSerializerDataFormat();
    }

    public function testGetMimeTypes()
    {
        $mimeTypes = array('application/vnd.php.serialized');
        $this->assertEquals($mimeTypes, $this->object->getMimeTypes());
    }

    public function testSerialize()
    {
        $object = new \stdClass();
        $object->property = 'hehe';
        $object->property2 = 1;

        $serialized = 'O:8:"stdClass":2:{s:8:"property";s:4:"hehe";s:9:"property2";i:1;}';
        $this->assertSame($serialized, $this->object->serialize($object));
    }

    public function testUnserialize()
    {
        $object = new \stdClass();
        $object->property = 'hehe';
        $object->property2 = 1;

        $serialized = 'O:8:"stdClass":2:{s:8:"property";s:4:"hehe";s:9:"property2";i:1;}';
        $this->assertEquals($object, $this->object->unserialize($serialized));
    }
}
