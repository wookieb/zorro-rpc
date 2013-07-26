<?php
namespace Wookieb\ZorroRPC\Tests\Serializer\DataFormat;
require_once __DIR__.'/ExampleClass.php';

use Wookieb\ZorroRPC\Serializer\DataFormat\PHPNativeSerializerDataFormat;
use Wookieb\ZorroRPC\Tests\Serializer\DataFormat\ExampleClass;

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
        $object = new ExampleClass('1', 2, array('3'));

        $serialized = 'O:57:"Wookieb\ZorroRPC\Tests\Serializer\DataFormat\ExampleClass":3:{s:74:"\000Wookieb\ZorroRPC\Tests\Serializer\DataFormat\ExampleClass\000privateProperty";s:1:"1";s:20:"\000*\000protectedProperty";i:2;s:14:"publicProperty";a:1:{i:0;s:1:"3";}}';
        $serialized = $this->prepareSerializedString($serialized);
        $this->assertSame($serialized, $this->object->serialize($object));
    }

    private function prepareSerializedString($string) {
        return str_replace('\000', "\000", $string);
    }
    public function testUnserialize()
    {
        $object = new ExampleClass('1', 2, array('3'));
        $serialized = 'O:57:"Wookieb\ZorroRPC\Tests\Serializer\DataFormat\ExampleClass":3:{s:74:"\000Wookieb\ZorroRPC\Tests\Serializer\DataFormat\ExampleClass\000privateProperty";s:1:"1";s:20:"\000*\000protectedProperty";i:2;s:14:"publicProperty";a:1:{i:0;s:1:"3";}}';
        $serialized = $this->prepareSerializedString($serialized);
        $this->assertEquals($object, $this->object->unserialize($serialized));
    }
}
