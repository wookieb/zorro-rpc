<?php

namespace Wookieb\ZorroRPC\Tests\Server;
use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Server\Method;
use Wookieb\ZorroRPC\Server\MethodTypes;
use Wookieb\ZorroRPC\Transport\Request;

class MethodTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Headers
     */
    private $headers;
    /**
     * @var \Closure
     */
    private $callback;

    protected function setUp()
    {
        $this->request = $this->getMock('Wookieb\ZorroRPC\Transport\Request');
        $this->headers = $this->getMock('Wookieb\ZorroRPC\Headers\Headers');
    }

    public function testShouldDetectNumOfRequiredArguments()
    {
        $method = new Method('name', function ($arg1, $arg2, $arg3) {
        });
        $this->assertEquals(array(), $method->getDefaultArguments());
        $this->assertSame(3, $method->getNumOfRequiredArguments());
    }

    public function testShouldDetectNumOfRequiredArgumentsWhenSomeOfThemAreOptional()
    {
        $method = new Method('name', function ($arg1, $arg2, $arg3 = 1) {
        });
        $this->assertEquals(array(
            2 => 1
        ), $method->getDefaultArguments());
        $this->assertSame(2, $method->getNumOfRequiredArguments());
    }

    public function testShouldBeAbleToCallCallback()
    {
        $test = $this;
        $request = $this->request;
        $headers = $this->headers;
        $called = false;
        $method = new Method('name', function ($arg1, $arg2 = 1) use ($test, $request, $headers, &$called) {
            $test->assertSame(2, $arg1);
            $test->assertSame(1, $arg2);
            $test->assertSame($request, func_get_arg(2));
            $this->assertSame($headers, func_get_arg(3));
            $called = true;
        });

        $method->call(array(2), $this->request, $this->headers);
        $this->assertTrue($called, 'method was not called');
    }

    public function testCallbackArgumentShouldNotBeAbleForMethodTypesOtherThanPush()
    {
        $method = new Method('name', function () {
        });
        $this->setExpectedException('\InvalidArgumentException', 'only available for PUSH methods');
        $method->call(array(1), $this->request, $this->headers, function () {

        });
    }

    public function testCallbackArgumentShouldBePlacesBeforeRequestArgument()
    {
        $test = $this;
        $request = $this->request;
        $headers = $this->headers;
        $called = false;
        $method = new Method('name', function ($arg1, $arg2 = 1) use ($test, $request, $headers, &$called) {
            $test->assertSame(2, $arg1);
            $test->assertSame(1, $arg2);
            $test->assertInstanceOf('\Closure', func_get_arg(2));
            $test->assertSame($request, func_get_arg(3));
            $this->assertSame($headers, func_get_arg(4));
            $called = true;

        }, MethodTypes::PUSH);

        $method->call(array(2), $this->request, $this->headers, function () {

        });
        $this->assertTrue($called, 'method was not called');
    }

    public function testShouldThrowExceptionWhenCallHasInsufficientNumberOfArguments()
    {
        $method = new Method('name', function ($arg1, $arg2) {
        });
        $this->setExpectedException('\InvalidArgumentException', 'Insufficient number of arguments');
        $method->call(array(1), $this->request, $this->headers);
    }

    public function testHeadersArgumentMayBeOmittedForOneWayCallMethods()
    {
        $test = $this;
        $request = $this->request;
        $called = false;
        $method = new Method('name', function ($arg1, Request $arg, Headers $headers = null) use ($test, $request, &$called) {
            $test->assertSame(2, $arg1);
            $test->assertSame($request, func_get_arg(1));
            $this->assertNull($headers);
            $called = true;
        }, MethodTypes::ONE_WAY);

        $method->call(array(2), $this->request);
        $this->assertTrue($called, 'method was not called');
    }

    public function testShouldThrowExceptionWhenResponseHeadersArgumentIsNotProvidedForMethodsOtherThanOneWay()
    {
        $method = new Method('name', function () {

        });
        $this->setExpectedException('\InvalidArgumentException', 'Response headers argument is required');
        $method->call(array(), $this->request);
    }

    public function testMethodNameCannotBeEmpty()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Method name cannot be empty');
        new Method('', 'array_map');
    }

    public function testMethodCallableMustBeCallable()
    {
        $this->setExpectedException('\InvalidArgumentException', 'callback must be callable');
        new Method('name', false);
    }

    public function testMethodTypeMustBeOnListOfAvailableMethodTypes()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Unsupported method type');
        new Method('name', 'array_map', 'yolo');
    }
}
