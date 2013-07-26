<?php
namespace Wookieb\ZorroRPC\Serializer\DataFormat;

use Wookieb\ZorroRPC\Exception\SerializationException;
use Wookieb\ZorroRPC\Serializer\DataFormat\DataFormatInterface;

class JSONDataFormat implements DataFormatInterface
{
    const VISIBILITY_PRIVATE = 1024;
    const VISIBILITY_PROTECTED = 512;
    const VISIBILITY_PUBLIC = 256;
    const VISIBILITY_ALL = 1792;

    private $serializedPropertiesVisibility = 1792;

    public function __construct($serializedPropertiesVisibility = null)
    {
        if ($serializedPropertiesVisibility) {
            $this->setSerializedPropertiesVisibility($serializedPropertiesVisibility);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function serialize($data)
    {
        $data = $this->prepareDataToSerialization($data);
        return json_encode($data);
    }

    /**
     * When object is serialized we need to know which properties (private, protected or public) must be serialized.
     * First option is to use __sleep method of target object to get list of keys to serialize and we do that.
     * When __sleep is not implemented we get list of properties of object from ReflectionObject instance.
     * This method set filter flag for getting properties with given visiblity.
     *
     * @param integer $visibility
     * @return self
     */
    public function setSerializedPropertiesVisibility($visibility)
    {
        $this->serializedPropertiesVisibility = $visibility;
        return $this;
    }

    private function prepareDataToSerialization($data)
    {
        switch (true) {
            case is_object($data):
                $reflection = new \ReflectionObject($data);

                if (method_exists($data, '__sleep')) {
                    $propertiesToSerialize = $data->__sleep();
                    if (!is_array($propertiesToSerialize)) {
                        $msg = 'Invalid data type returned from method __sleep from object of class '.get_class($data).'.';
                        $msg .= '__sleep must return array of keys to serialize.';
                        throw new SerializationException($msg);
                    }
                } else {
                    $visibility = $this->serializedPropertiesVisibility;

                    // default visibility level for json_encode
                    if ($visibility === self::VISIBILITY_PUBLIC) {
                        return $data;
                    }

                    $propertiesToSerialize = array_map(function ($property) use ($visibility) {
                        /* @var $property \ReflectionProperty */
                        return $property->getName();
                    }, $reflection->getProperties($this->serializedPropertiesVisibility));
                }

                $dataToSerialize = array();
                foreach ($propertiesToSerialize as $propertyName) {
                    $property = $reflection->getProperty($propertyName);
                    $property->setAccessible(true);
                    $dataToSerialize[$propertyName] = $this->prepareDataToSerialization($property->getValue($data));
                }
                return $dataToSerialize;
                break;
            case is_array($data):
                foreach ($data as &$value) {
                    $value = $this->prepareDataToSerialization($value);
                }
                break;
        }
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($data)
    {
        return json_decode($data);
    }

    /**
     * {@inheritDoc}
     */
    public function getMimeTypes()
    {
        return array('application/json');
    }
}