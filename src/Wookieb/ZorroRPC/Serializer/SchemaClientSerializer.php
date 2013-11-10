<?php

namespace Wookieb\ZorroRPC\Serializer;

use Wookieb\ZorroRPC\Exception\SerializationException;
use Wookieb\ZorroRPC\RPCSchema\ArgumentSchema;

/**
 * @author Łukasz Kużyński "wookieb" <lukasz.kuzynski@gmail.com>
 */
class SchemaClientSerializer extends AbstractSchemaSerializer implements ClientSerializerInterface
{
    private $sendDefaultValues = true;

    /**
     * @param boolean $send
     * @return self
     */
    public function sendDefaultValues($send)
    {
        $this->sendDefaultValues = (bool)$send;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isSendingDefaultValues()
    {
        return $this->sendDefaultValues;
    }

    /**
     * {@inheritDoc}
     */
    public function serializeArguments($method, array $arguments, $mimeType = null)
    {
        $methodSchema = $this->getMethodSchema($method);

        $argNumber = 0;
        $serializedArguments = array();
        $dataFormat = $this->getDataFormatForMimeType($mimeType);
        foreach ($methodSchema->getArguments() as $argumentSchema) {
            /** @var ArgumentSchema $argumentSchema */
            if (array_key_exists($argNumber, $arguments)) {
                $argument = $argumentSchema->getType()->extract($arguments[$argNumber]);
            } else if ($argumentSchema->hasDefaultValue()) {
                if ($this->sendDefaultValues) {
                    $argument = $argumentSchema->getType()->extract($argumentSchema->getDefaultValue());
                } else {
                    $argument = null;
                }
            } else if ($argumentSchema->isNullable()) {
                $argument = null;
            } else {
                $msg = vsprintf('Argument "%s" (%d) for method "%s" is required', array(
                    $argumentSchema->getName(),
                    $argNumber,
                    $method
                ));
                throw new SerializationException($msg);
            }
            $serializedArguments[] = $dataFormat->serialize($argument);
            $argNumber++;
        }
        return $serializedArguments;
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeResult($method, $result, $mimeType = null)
    {
        $methodSchema = $this->getMethodSchema($method);
        $resultSchema = $methodSchema->getResultSchema();
        if ($result === null && $resultSchema->isNullable()) {
            return null;
        }
        $result = $this->getDataFormatForMimeType($mimeType)->unserialize($result);
        if ($result === null && $resultSchema->isNullable()) {
            return null;
        }
        return $resultSchema->getType()->create($result);
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeError($method, $error, $mimeType = null)
    {
        $methodSchema = $this->getMethodSchema($method);
        $errorData = $this->getDataFormatForMimeType($mimeType)->unserialize($error);
        return $methodSchema->getExceptionType()->create($errorData);
    }
} 
