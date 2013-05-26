<?php
namespace Wookieb\ZorroRPC\Headers;
use Wookieb\ZorroRPC\Headers\Parser;
use Wookieb\ZorroRPC\Exception\InvalidHeaderException;

/**
 * Container for headers
 *
 * @author wookieb <wookieb@wp.pl>
 */
class Headers implements \IteratorAggregate
{
    private $headers = array();

    public function __construct(array $headers = array())
    {
        if ($headers !== array()) {
            $this->set($headers);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->headers);
    }

    /**
     * Set particular header value
     *
     * @param string|array $name string or array of headers where keys are treated as header names
     * @param string $value
     *
     * @return self
     * @throws \Wookieb\ZorroRPC\Exception\InvalidHeaderException
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $headerName => $headerValue) {
                $this->set($headerName, $headerValue);
            }
            return $this;
        }

        if (!Parser::isValidHeaderName($name)) {
            throw new InvalidHeaderException('Invalid header name "'.$name.'"');
        }
        $this->headers[strtolower($name)] = $value;
        return $this;
    }

    /**
     * Returns value of given header
     *
     * @param string $name
     * @return string|null if header not set
     */
    public function get($name)
    {
        if ($this->has($name)) {
            return $this->headers[strtolower($name)];
        }
    }

    /**
     * Check whether container has given header
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * Return all headers
     *
     * @return array
     */
    public function getAll()
    {
        return $this->headers;
    }

    public function __toString()
    {
        $headers = '';
        foreach ($this->headers as $headerName => $headerValue) {
            $headers .= $headerName.':'.$headerValue."\n";
        }
        return $headers;
    }
}