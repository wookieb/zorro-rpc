<?php

namespace Wookieb\ZorroRPC\Serializer;


use Wookieb\ZorroRPC\Exception\DataFormatNotFoundException;

class SchemalessClientSerializer extends AbstractSerializer implements ClientSerializerInterface
{
    /**
     * {@inheritDoc}
     */
    public function serializeArguments($method, array $arguments, $mimeType = null)
    {
        $dataFormat = $this->getDataFormatForMimeType($mimeType);
        return array_map(array($dataFormat, 'serialize'), $arguments);
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeResult($method, $result, $mimeType = null)
    {
        return $this->getDataFormatForMimeType($mimeType)->unserialize($result);
    }

    /**
     * {@inheritDoc}
     */
    function unserializeError($method, $error, $mimeType = null)
    {
        return $this->getDataFormatForMimeType($mimeType)->unserialize($error);
    }


}