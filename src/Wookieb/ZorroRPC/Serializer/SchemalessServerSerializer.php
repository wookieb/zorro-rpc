<?php

namespace Wookieb\ZorroRPC\Serializer;

/**
 * Serializer that works without schema
 *
 *
 */
class SchemalessServerSerializer extends AbstractSerializer implements ServerSerializerInterface
{
    /**
     * {@inheritDoc}
     */
    public function unserializeArguments($method, array $arguments, $mimeType = null)
    {
        $unserialized = array();
        $dataFormat = $this->getDataFormatForMimeType($mimeType);
        foreach ($arguments as $argument) {
            $unserialized[] = $dataFormat->unserialize($argument);
        }
        return $unserialized;
    }

    /**
     * {@inheritDoc}
     */
    public function serializeResult($method, $result, $mimeType = null)
    {
        return $this->getDataFormatForMimeType($mimeType)->serialize($result);
    }
}