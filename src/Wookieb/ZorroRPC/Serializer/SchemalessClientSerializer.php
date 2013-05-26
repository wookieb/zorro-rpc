<?php

namespace Wookieb\ZorroRPC\Serializer;


class SchemalessClientSerializer extends AbstractSerializer implements ClientSerializerInterface
{
    /**
     * {@inheritDoc}
     */
    public function serializeArguments($method, array $arguments, $mimeType = null)
    {
        return $this->getDataFormatForMimeType($mimeType)->serialize($arguments);
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeResult($method, $result, $mimeType = null)
    {
        return $this->getDataFormatForMimeType($mimeType)->unserialize($result);
    }

}