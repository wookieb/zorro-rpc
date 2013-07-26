<?php

namespace Wookieb\ZorroRPC\Tests\Serializer\DataFormat;
require_once __DIR__.'/ExampleClass.php';
require_once __DIR__.'/ExampleClassWithSleep.php';

use Wookieb\ZorroRPC\Serializer\DataFormat\JSONDataFormat;
use Wookieb\ZorroRPC\Tests\Serializer\DataFormat\ExampleClass;
use Wookieb\ZorroRPC\Tests\Serializer\DataFormat\ExampleClassWithSleep;

class JSONDataFormatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JSONDataFormat
     */
    private $object;

    protected function setUp()
    {
        $this->object = new JSONDataFormat();
    }

    public function testGetMimeTypes()
    {
        $this->assertEquals(array('application/json'), $this->object->getMimeTypes());
    }

    public function testSetGetSerializedPropertiesVisibility()
    {
        $visibility = JSONDataFormat::VISIBILITY_PRIVATE | JSONDataFormat::VISIBILITY_PROTECTED;
        $result = $this->object->setSerializedPropertiesVisibility($visibility);
        $this->assertSame($this->object, $result, 'method chaining violation at "setSerializedPropertiesVisibility"');
        $this->assertSame($visibility, $this->object->getSerializedPropertiesVisibility());
    }

    public function testSerializedPropertiesVisibilityCanBeChangedFromConstructor()
    {
        $visibility = JSONDataFormat::VISIBILITY_PRIVATE | JSONDataFormat::VISIBILITY_PROTECTED;
        $this->object = new JSONDataFormat($visibility);
        $this->assertSame($visibility, $this->object->getSerializedPropertiesVisibility());
    }

    public function testSerializationWithSleep()
    {
        $target = new ExampleClassWithSleep(1, 2, 3);
        $this->assertSame('{"privateProperty":1,"protectedProperty":2}', $this->object->serialize($target));
    }

    public function testSerializationWithPrivateProperties()
    {
        $target = new ExampleClass(1, 2, 3);
        $this->object->setSerializedPropertiesVisibility(JSONDataFormat::VISIBILITY_PRIVATE);
        $this->assertSame('{"privateProperty":1}', $this->object->serialize($target));
    }

    public function testSerializationWithProtectedProperties()
    {
        $target = new ExampleClass(1, 2, 3);
        $this->object->setSerializedPropertiesVisibility(JSONDataFormat::VISIBILITY_PROTECTED);
        $this->assertSame('{"protectedProperty":2}', $this->object->serialize($target));
    }

    public function testSerializationWithPublicProperties()
    {
        $target = new ExampleClass(1, 2, 3);
        $this->object->setSerializedPropertiesVisibility(JSONDataFormat::VISIBILITY_PUBLIC);
        $this->assertSame('{"publicProperty":3}', $this->object->serialize($target));
    }

    public function testSerializeAllPropertiesByDefault()
    {
        $target = new ExampleClass(1, 2, 3);
        $this->assertSame('{"privateProperty":1,"protectedProperty":2,"publicProperty":3}', $this->object->serialize($target));
    }

    public function testShouldBeAbleToSerializeScalarValues()
    {
        $this->assertSame('1', $this->object->serialize(1), 'cant serialize numbers');
        $this->assertSame('"1"', $this->object->serialize('1'), 'cant serialize strings');
        $this->assertsame('[1,2]', $this->object->serialize(range(1, 2)), 'cant serialize arrays');
    }

    public function testUnserializeObjects()
    {
        $target = new \stdClass();
        $target->privateProperty = 1;
        $target->protectedProperty = 2;
        $target->publicProperty = 3;

        $dataToUnserialize = '{"privateProperty":1,"protectedProperty":2,"publicProperty":3}';
        $this->assertEquals($target, $this->object->unserialize($dataToUnserialize));
    }

    public function testUnserializeScalarValues()
    {
        $this->assertSame(1, $this->object->unserialize('1'), 'cant unserialize numbers');
        $this->assertSame('1', $this->object->unserialize('"1"'), 'cant unserialize strings');
        $this->assertsame(range(1, 2), $this->object->unserialize('[1,2]'), 'cant unserialize arrays');
    }
}
