<?php
namespace Wookieb\ZorroRPC\Transport;
use Wookieb\ZorroRPC\Headers\Headers;

/**
 * Data structure for request
 *
 * @author wookieb <wookieb@wp.pl>
 */
class Request
{
    private $type;
    private $argumentsBody = array();
    private $methodName;
    private $headers;

    public function __construct($type = null, $method = null, array $arguments = null, Headers $headers = null)
    {
        if ($type) {
            $this->setType($type);
        }
        if ($method) {
            $this->setMethodName($method);
        }
        if ($arguments) {
            $this->setArgumentsBody($arguments);
        }
        if ($headers) {
            $this->setHeaders($headers);
        } else {
            $this->setHeaders(new Headers());
        }
    }

    /**
     * @param array $arguments
     *
     * @return self
     */
    public function setArgumentsBody(array $arguments)
    {
        $this->argumentsBody = $arguments;
        return $this;
    }

    /**
     * @return array
     */
    public function getArgumentsBody()
    {
        return $this->argumentsBody;
    }

    /**
     * @param string $methodName
     * @return self
     */
    public function setMethodName($methodName)
    {
        $this->methodName = (string)$methodName;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethodName()
    {
        return $this->methodName;
    }

    /**
     * @param integer $type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * @param Headers $headers
     * @return self
     */
    public function setHeaders(Headers $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @return Headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Indicate that request expects result in corresponding response
     *
     * @return bool
     */
    public function isExpectingResult()
    {
        return $this->type !== MessageTypes::PING && $this->type !== MessageTypes::ONE_WAY_CALL;
    }
}
