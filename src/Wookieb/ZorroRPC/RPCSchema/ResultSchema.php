<?php

namespace Wookieb\ZorroRPC\RPCSchema;
use Wookieb\ZorroDataSchema\Schema\Type\TypeInterface;

/**
 * @author Łukasz Kużyński "wookieb" <lukasz.kuzynski@gmail.com>
 */
class ResultSchema
{
    /**
     * @var \Wookieb\ZorroDataSchema\Schema\Type\TypeInterface
     */
    private $type;
    private $isNullable;

    public function __construct(TypeInterface $type, $isNullable = true)
    {
        $this->type = $type;
        $this->isNullable = (bool)$isNullable;
    }

    /**
     * @return TypeInterface
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return boolean
     */
    public function isNullable()
    {
        return $this->isNullable;
    }
} 
