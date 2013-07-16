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
        $dataFormat = $this->getDataFormatForMimeType($mimeType);
        return array_map(array($dataFormat, 'unserialize'), $arguments);
    }

    /**
     * {@inheritDoc}
     */
    public function serializeResult($method, $result, $mimeType = null)
    {
        return $this->getDataFormatForMimeType($mimeType)->serialize($result);
    }
}