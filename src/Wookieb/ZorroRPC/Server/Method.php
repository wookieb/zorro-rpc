<?php
namespace Wookieb\ZorroRPC\Server;

/**
 * Definition of RPC method
 *
 * @author wookieb <wookieb@wp.pl>
 */
class Method
{
    private $name;
    private $callback;
    private $type;

    /**
     * @param string $name
     * @param callback $callback
     * @param int $type one of value from MethodTypes dictionary
     *
     */
    public function __construct($name, $callback, $type = MethodTypes::BASIC)
    {
        $name = trim($name);
        if (!$name) {
            throw new \InvalidArgumentException('Method name cannot be empty');
        }

        if (!is_callable($callback, true)) {
            throw new \InvalidArgumentException('Method callback must be callable');
        }

        if (!MethodTypes::isValid($type)) {
            throw new \InvalidArgumentException('Unsupported method type');
        }

        $this->name = $name;
        $this->callback = $callback;
        $this->type = $type;
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

}