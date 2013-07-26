<?php

namespace Wookieb\ZorroRPC\Serializer;
use Wookieb\ZorroRPC\Exception\DataFormatNotFoundException;
use Wookieb\ZorroRPC\Exception\SerializationException;
use Wookieb\ZorroRPC\Exception\ExceptionChanger;
use Wookieb\ZorroRPC\Serializer\DataFormat\DataFormatInterface;

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

    /**
     * {@inheritDoc}
     */
    function serializeError($method, \Exception $error, $mimeType = null)
    {
        return $this->getDataFormatForMimeType($mimeType)->serialize($error);
    }
}