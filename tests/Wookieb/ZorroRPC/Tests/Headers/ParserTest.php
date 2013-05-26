<?php

namespace Wookieb\ZorroRPC\Tests\Headers;
use Wookieb\ZorroRPC\Headers\Parser;
use Wookieb\ZorroRPC\Exception\InvalidHeaderException;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParsingHeaders() {
        $headers = 'Header1:value1'."\n";
        $headers .= 'header2:value2'."\n";

        $expected = array(
            'Header1' => 'value1',
            'header2' => 'value2'
        );

        $this->assertEquals($expected, Parser::parseHeaders($headers));
    }

    public function testParsingInvalidHeadersShouldThrowException() {
        $msg = 'Invalid header format';
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\InvalidHeaderException', $msg);
        $headers = 'invalidHeader';
        Parser::parseHeaders($headers);
    }

    public function testParsingHeaderWithInvalidNameShouldThrowException() {
        $msg = 'Invalid header name "bad boy header"';
        $this->setExpectedException('Wookieb\ZorroRPC\Exception\InvalidHeaderException', $msg);
        $headers = 'bad boy header:test';
        Parser::parseHeaders($headers);
    }
}