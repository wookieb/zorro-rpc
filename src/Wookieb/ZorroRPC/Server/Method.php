<?php
namespace Wookieb\ZorroRPC\Server;
use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Transport\Request;

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

    private $numOfRequiredArguments = 0;
    private $defaultArguments = array();

    private static $zorroRpcClasses = array(
        'Wookieb\ZorroRPC\Transport\Request',
        'Wookieb\ZorroRPC\Headers\Headers'
    );

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

        $this->extractCallbackInfo();
    }

    private function extractCallbackInfo()
    {
        if (is_array($this->callback)) {
            $callbackInfo = new \ReflectionMethod($this->callback[0], $this->callback[1]);
        } else {
            $callbackInfo = new \ReflectionFunction($this->callback);
        }
        $this->numOfRequiredArguments = 0;
        $this->defaultArguments = array();
        $parameters = $callbackInfo->getParameters();
        $parameters = $this->removeZorroRPCParameters($parameters);
        foreach ($parameters as $key => $parameter) {
            try {
                $this->defaultArguments[$key] = $parameter->getDefaultValue();
            } catch (\ReflectionException $e) {
                $this->numOfRequiredArguments++;
            }
        }
    }

    /**
     * @param array $parameters
     * @return \ReflectionParameter[]
     */
    private function removeZorroRPCParameters(array $parameters)
    {
        $last = end($parameters);
        /* @var \ReflectionParameter $last */
        if ($last && $last->getClass() && in_array($last->getClass()->getName(), self::$zorroRpcClasses)) {
            array_pop($parameters);
        }

        $last = end($parameters);
        /* @var \ReflectionParameter $last */
        if ($last && $last->getClass() && in_array($last->getClass()->getName(), self::$zorroRpcClasses)) {
            array_pop($parameters);
        }

        $last = end($parameters);
        /* @var \ReflectionParameter $last */
        if ($this->type === MethodTypes::PUSH && $last && $last->getClass() && $last->getClass()->getName() === '\Closure') {
            array_pop($parameters);
        }

        return $parameters;
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

    public function call(array $arguments, Request $request, Headers $responseHeaders = null, $callback = null)
    {
        if (count($arguments) < $this->numOfRequiredArguments) {
            $msg = 'Insufficient number of arguments. ' . $this->numOfRequiredArguments . ' required';
            throw new \InvalidArgumentException($msg);
        }
        if ($callback && $this->type !== MethodTypes::PUSH) {
            throw new \InvalidArgumentException('Callback argument is only available for PUSH methods');
        }
        $args = $arguments + $this->defaultArguments;
        if ($callback) {
            $args[] = $callback;
        }
        $args[] = $request;
        if ($responseHeaders) {
            $args[] = $responseHeaders;
        }
        return call_user_func_array($this->callback, $args);
    }
}