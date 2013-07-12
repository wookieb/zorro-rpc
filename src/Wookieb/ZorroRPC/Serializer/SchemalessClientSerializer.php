<?php

namespace Wookieb\ZorroRPC\Serializer;


class SchemalessClientSerializer extends AbstractSerializer implements ClientSerializerInterface
{
    /**
     * {@inheritDoc}
     */
    public function serializeArguments($method, array $arguments, $mimeType = null)
    {
        $serialized = array();
        $dataFormat = $this->getDataFormatForMimeType($mimeType);
        foreach ($arguments as $argument) {
            $serialized[] = $dataFormat->serialize($argument);
        }
        return $serialized;
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeResult($method, $result, $mimeType = null)
    {
        return $this->getDataFormatForMimeType($mimeType)->unserialize($result);
    }

}