<?php

namespace Wookieb\ZorroRPC\Serializer;

use Wookieb\ZorroRPC\Exception\NoSuchMethodException;
use Wookieb\ZorroRPC\Exception\SerializationException;
use Wookieb\ZorroRPC\RPCSchema\RPCSchema;
use Wookieb\ZorroRPC\Serializer\DataFormat\DataFormatInterface;

/**
 * @author Łukasz Kużyński "wookieb" <lukasz.kuzynski@gmail.com>
 */
abstract class AbstractSchemaSerializer extends AbstractSerializer
{
    /**
     * @var RPCSchema
     */
    protected $schema;

    public function __construct(RPCSchema $schema, DataFormatInterface $defaultDataFormat = null)
    {
        $this->schema = $schema;
        parent::__construct($defaultDataFormat);
    }

    /**
     * @return RPCSchema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @param string $methodName
     * @return \Wookieb\ZorroRPC\RPCSchema\MethodSchema
     * @throws \Wookieb\ZorroRPC\Exception\SerializationException
     */
    protected function getMethodSchema($methodName)
    {
        try {
            $methodSchema = $this->schema->getMethod($methodName);
        } catch (NoSuchMethodException $exception) {
            throw new SerializationException('Undefined method "'.$methodName.'" in schema');
        }
        return $methodSchema;
    }
} 
