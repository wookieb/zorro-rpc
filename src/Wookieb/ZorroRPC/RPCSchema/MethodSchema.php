<?php

namespace Wookieb\ZorroRPC\RPCSchema;
use Wookieb\Assert\Assert;
use Wookieb\ZorroDataSchema\Schema\Type\TypeInterface;

/**
 * Schema that describes name, list of arguments, returned type, exception type of method
 *
 * @author Łukasz Kużyński "wookieb" <lukasz.kuzynski@gmail.com>
 */
class MethodSchema
{
    private $name;
    private $arguments;
    /**
     * @var ResultSchema
     */
    private $resultSchema;
    /**
     * @var TypeInterface
     */
    private $exceptionType;

    /**
     * @param string $name method name
     * @param ResultSchema $resultSchema
     * @param string $exceptionTypeName
     */
    public function __construct($name, ResultSchema $resultSchema = null, TypeInterface $exceptionTypeName = null)
    {
        $this->setName($name);
        $this->setResultSchema($resultSchema);
        $this->setExceptionType($exceptionTypeName);
    }

    /**
     * @param ArgumentSchema $argument
     * @return self
     */
    public function addArgument(ArgumentSchema $argument)
    {
        $this->arguments[] = $argument;
        return $this;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    private function setName($name)
    {
        Assert::notBlank($name, 'Method name cannot be empty');
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    private function setExceptionType(TypeInterface $errorTypeName)
    {
        $this->exceptionType = $errorTypeName;
    }

    /**
     * @return TypeInterface
     */
    public function getExceptionType()
    {
        return $this->exceptionType;
    }

    private function setResultSchema(ResultSchema $resultSchema)
    {
        $this->resultSchema = $resultSchema;
    }

    /**
     * @return ResultSchema
     */
    public function getResultSchema()
    {
        return $this->resultSchema;
    }
}
