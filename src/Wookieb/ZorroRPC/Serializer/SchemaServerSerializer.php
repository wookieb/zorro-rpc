<?php

namespace Wookieb\ZorroRPC\Serializer;

use Wookieb\ZorroRPC\Exception\SerializationException;
use Wookieb\ZorroRPC\RPCSchema\ArgumentSchema;

/**
 * @author Łukasz Kużyński "wookieb" <lukasz.kuzynski@gmail.com>
 */
class SchemaServerSerializer extends AbstractSchemaSerializer implements ServerSerializerInterface
{
    /**
     * {@inheritDoc}
     */
    public function unserializeArguments($method, array $arguments, $mimeType = null)
    {
        $methodSchema = $this->getMethodSchema($method);
        $resultArguments = array();
        foreach ($methodSchema->getArguments() as $argumentKey => $argumentSchema) {
            /** @var ArgumentSchema $argumentSchema */
            if (array_key_exists($argumentKey, $arguments)) {
                $argument = $arguments[$argumentKey];
            } else if ($argumentSchema->isNullable()) {
                $argument = null;
            } else if ($argumentSchema->getDefaultValue()) {
                $argument = $argumentSchema->getDefaultValue();
            } else {
                $msg = vsprintf('Argument %s (%d) for method %s is required', array(
                    $argumentSchema->getName(),
                    $argumentKey,
                    $method
                ));
                throw new SerializationException($msg);
            }
            $resultArguments[] = $argument;
        }
        return $resultArguments;
    }

    /**
     * {@inheritDoc}
     */
    public function serializeResult($method, $result, $mimeType = null)
    {
        $schema = $this->getMethodSchema($method);
        $resultSchema = $schema->getResultSchema();
        if ($result === null && $resultSchema->isNullable()) {
            return $result;
        }
        $dataToSerialize = $resultSchema->getType()->extract($result);
        return $this->getDataFormatForMimeType($mimeType)->serialize($dataToSerialize);
    }

    /**
     * {@inheritDoc}
     */
    public function serializeError($method, \Exception $error, $mimeType = null)
    {
        $schema = $this->getMethodSchema($method);
        $data = $schema->getExceptionType()->extract($error);
        return $this->getDataFormatForMimeType($mimeType)->serialize($data);
    }

} 
