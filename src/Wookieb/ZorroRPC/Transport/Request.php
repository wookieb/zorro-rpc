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
    private $argumentsBody;
    private $methodName;
    private $headers;

    /**
     * @param array $arguments
     *
     * @return self
     */
    public function setArgumentsBody($arguments)
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
        $this->type = (int)$type;
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
}