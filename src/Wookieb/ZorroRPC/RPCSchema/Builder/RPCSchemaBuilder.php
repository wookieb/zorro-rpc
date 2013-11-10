<?php

namespace Wookieb\ZorroRPC\RPCSchema\Builder;

use Wookieb\ZorroDataSchema\Schema\SchemaInterface;

/**
 * @author Łukasz Kużyński "wookieb" <lukasz.kuzynski@gmail.com>
 */
class RPCSchemaBuilder
{
    private $dataSchema;

    public function setDataSchema(SchemaInterface $dataSchema)
    {
        $this->dataSchema = $dataSchema;
        return $this;
    }

    public function getDataSchema()
    {
        return $this->dataSchema;
    }
}
