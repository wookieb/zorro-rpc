<?php

namespace Wookieb\ZorroRPC\Serializer;
use Wookieb\ZorroRPC\Serializer\DataFormat\DataFormatInterface;
use Wookieb\ZorroRPC\Exception\DataFormatNotFoundException;

/**
 * Base methods for serializers
 *
 * @author wookieb <wookieb@wp.pl>
 */
abstract class AbstractSerializer
{
    private $serializers = array();
    private $defaultMimeType;

    /**
     * Register data format and use it by default for all operations without defined mime type
     *
     * @param DataFormatInterface $dataFormats
     * @return self
     */
    public function setDefaultDataFormat(DataFormatInterface $dataFormat)
    {
        $this->registerDataFormat($dataFormat);
        $this->defaultMimeType = current($dataFormat->getMimeTypes());
        return $this;
    }

    /**
     * Return current default data format
     *
     * @return DataFormatInterface null if not defined
     */
    public function getDefaultDataFormat()
    {
        if ($this->defaultMimeType) {
            return $this->getDataFormatForMimeType($this->defaultMimeType);
        }
    }

    /**
     * Return current default data format mime type
     *
     * @return string null if not defined
     */
    public function getDefaultMimeType()
    {
        return $this->defaultMimeType;
    }

    /**
     * Register data format in list of formats used for serialization
     *
     * @param DataFormatInterface $dataFormat
     * @return self
     */
    public function registerDataFormat(DataFormatInterface $dataFormat)
    {
        $this->checkDataFormat($dataFormat);
        $mimeTypes = $dataFormat->getMimeTypes();
        foreach ($mimeTypes as $mimeType) {
            $this->serializers[$mimeType] = $dataFormat;
        }
        return $this;
    }

    private function checkDataFormat(DataFormatInterface $dataFormat)
    {
        $mimeTypes = $dataFormat->getMimeTypes();
        if (!is_array($mimeTypes) || $mimeTypes === array()) {
            throw new \UnexpectedValueException('Data format must returns array of supported mime types');
        }
    }

    /**
     * Return dataformat that is able to handle given mime type
     * If mime type is null then default data format will be returned
     *
     * @param string $mimeType null if default data format should be returned
     * @return DataFormatInterface
     * @throws DataFormatNotFoundException
     */
    public function getDataFormatForMimeType($mimeType = null)
    {
        $mimeType = $mimeType ? $mimeType : $this->defaultMimeType;
        if ($mimeType === null) {
            throw new DataFormatNotFoundException('Default data format not defined');
        }
        if (!isset($this->serializers[$mimeType])) {
            throw new DataFormatNotFoundException('No data format defined for mime type "'.$mimeType.'"');
        }
        return $this->serializers[$mimeType];
    }
}