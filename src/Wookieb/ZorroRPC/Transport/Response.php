<?php
namespace Wookieb\ZorroRPC\Transport;
use Wookieb\ZorroRPC\Headers\Headers;

/**
 * Data structure for response
 *
 * @package Wookieb\ZorroRPC
 */
class Response
{
    private $type;
    private $headers;
    private $resultBody;

    public function __construct($type = null)
    {
        if ($type) {
            $this->setType($type);
        }
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
     * @param string $resultBody
     * @return self
     */
    public function setResultBody($resultBody)
    {
        $this->resultBody = $resultBody;
        return $this;
    }

    /**
     * @return string
     */
    public function getResultBody()
    {
        return $this->resultBody;
    }

    /**
     * @param integer $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = (int)$type;
        return $this;
    }

    /**
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }
}