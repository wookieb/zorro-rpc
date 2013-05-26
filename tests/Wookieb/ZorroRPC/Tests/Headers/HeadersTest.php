<?php
namespace Wookieb\ZorroRPC\Tests\Headers;
use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Exception\InvalidHeaderException;
class HeadersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Headers
     */
    private $object;

    protected function setUp()
    {
        $this->object = new Headers(array(
            'header1' => 'value1',
            'header2' => 'value2'
        ));
    }

    public function testHeadersMayBySetFromConstructor()
    {
        $this->assertSame('value1', $this->object->get('header1'));
        $this->assertSame('value2', $this->object->get('header2'));
    }

    public function testToStringShouldReturnsStringWithHeadersToSend()
    {
        $expected = "header1:value1\nheader2:value2\n";
        $this->assertSame($expected, (string)$this->object);
    }

    public function testGetAllShouldReturnsAllHeaders()
    {
        $expected = array(
            'header1' => 'value1',
            'header2' => 'value2'
        );
        $this->assertEquals($expected, $this->object->getAll());
    }

    public function testHasShouldCheckWhetherHeaderIsSet()
    {
        $this->assertFalse($this->object->has('yetiHeader'));
        $this->assertTrue($this->object->has('header1'));
    }

    public function testSetShouldSetGivenHeader()
    {
        $result = $this->object->set('zorro', 'rpc');
        $this->assertSame($this->object, $result, 'Method chaining rule violation at "set"');
        $this->assertSame('rpc', $this->object->get('zorro'));
    }

    public function testHeaderNamesAreCaseInsensitive() {
        $this->assertSame('value1', $this->object->get('HeAder1'));
        $this->assertSame('value1', $this->object->get('header1'));
    }

    public function testGetShouldReturnNullWhenHeaderDoesNotExists() {
        $this->assertNull($this->object->get('yetiHeader'));
    }

    public function testSettingInvalidHeaderNameShouldThrowException() {
        $msg = 'Invalid header name "bad boy header"';
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\InvalidHeaderException', $msg);
        $this->object->set('bad boy header', 'kaboom');
    }

    public function testShouldBeTraversableOnHeaders() {
        $this->assertInstanceOf('\Traversable', $this->object);
        $this->assertInstanceOf('\Traversable', $this->object->getIterator());
    }
}