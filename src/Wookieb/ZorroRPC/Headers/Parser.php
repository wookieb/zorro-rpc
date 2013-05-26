<?php
namespace Wookieb\ZorroRPC\Headers;
use Wookieb\ZorroRPC\Exception\InvalidHeaderException;

/**
 * Headers string parser
 *
 * @author wookieb <wookieb@wp.pl>
 */
class Parser
{
    /**
     * Parse headers string
     *
     * @param string $headers
     * @return array list of headers
     * @throws InvalidHeaderException when one of headers is invalid
     */
    public static function parseHeaders($headers)
    {
        $headersArray = explode("\n", $headers);
        $headersArray = array_filter($headersArray);
        $headersContainer = array();
        foreach ($headersArray as $header) {
            list ($name, $value) = self::parseHeader($header);
            $headersContainer[$name] = $value;
        }
        return $headersContainer;
    }

    /**
     * Parse header line
     *
     * @param string $header
     * @return array which contains header name and header value (always string)
     * @throws InvalidHeaderException
     */
    public static function parseHeader($header)
    {
        $data = explode(':', $header, 2);
        if (count($data) < 2) {
            throw new InvalidHeaderException('Invalid header format');
        }
        if (!self::isValidHeaderName($data[0])) {
            throw new InvalidHeaderException('Invalid header name "'.$data[0].'"');
        }
        return $data;
    }

    /**
     * Check wheter header name is valid
     *
     * @param string $headerName
     * @return bool
     */
    public static function isValidHeaderName($headerName)
    {
        return (bool)preg_match('/^[a-z0-9\-_]+\n?$/i', $headerName);
    }
}