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
    public function unserializeArguments($method, $argumentsBody, $mimeType = null)
    {
        return (array)$this->getDataFormatForMimeType($mimeType)->unserialize($argumentsBody);
    }

    /**
     * {@inheritDoc}
     */
    public function serializeResult($method, $result, $mimeType = null)
    {
        return $this->getDataFormatForMimeType($mimeType)->serialize($result);
    }
}