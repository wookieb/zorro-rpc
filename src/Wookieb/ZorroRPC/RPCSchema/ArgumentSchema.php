<?php

namespace Wookieb\ZorroRPC\RPCSchema;
use Wookieb\Assert\Assert;
use Wookieb\ZorroDataSchema\Schema\Type\TypeInterface;

/**
 * Schema of method argument
 *
 * @author Łukasz Kużyński "wookieb" <lukasz.kuzynski@gmail.com>
 */
class ArgumentSchema
{
    private $name;
    /**
     * @var TypeInterface
     */
    private $type;
    private $hasDefaultValue = false;
    private $defaultValue;
    private $isNullable = false;

    public function __construct($name, TypeInterface $type)
    {
        $this->setName($name);
        $this->setType($type);
    }

    private function setName($name)
    {
        Assert::notBlank($name, 'Argument name cannot be blank');
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    private function setType(TypeInterface $type)
    {
        $this->type = $type;
    }

    /**
     * Returns name of type of argument
     *
     * @return TypeInterface
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns information whether argument has default value
     *
     * @return boolean
     */
    public function hasDefaultValue()
    {
        return $this->hasDefaultValue;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Sets default value of argument and marks it as "has default value"
     *
     * @param mixed $defaultValue
     * @throws \BadMethodCallException when argument is nullable
     * @return self
     */
    public function setDefaultValue($defaultValue)
    {
        if ($this->isNullable) {
            throw new \BadMethodCallException('Cannot set default value for argument since it\'s nullable');
        }
        $this->hasDefaultValue = true;
        if ($this->type->isTargetType($defaultValue)) {
            $defaultValue = $this->type->create($defaultValue);
        }
        $this->defaultValue = $defaultValue;
        return $this;
    }

    /**
     * Removes default value of argument
     *
     * @return self
     */
    public function removeDefaultValue()
    {
        $this->hasDefaultValue = false;
        $this->defaultValue = null;
        return $this;
    }

    /**
     * Sets flag whether argument may be null
     *
     * @param string $nullable
     * @throws \BadMethodCallException when property has default value
     * @return string
     */
    public function setIsNullable($nullable)
    {
        $nullable = (bool)$nullable;
        if ($this->hasDefaultValue && $nullable) {
            throw new \BadMethodCallException('Cannot set argument to be nullable since it has default value');
        }
        $this->isNullable = $nullable;
        return $this;
    }

    /**
     * Returns information whether argument may be null
     *
     * @return boolean
     */
    public function isNullable()
    {
        return $this->isNullable;
    }
}
