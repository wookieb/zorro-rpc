<?php
namespace Wookieb\ZorroRPC\Serializer\DataFormat;

/**
 * @author Łukasz Kużyński "wookieb" <lukasz.kuzynski@gmail.com>
 */
class PHPNativeSerializerDataFormat implements DataFormatInterface
{
    /**
     * {@inheritDoc}
     */
    public function getMimeTypes()
    {
        return array('application/vnd.php.serialized');
    }

    /**
     * {@inheritDoc}
     */
    function serialize($data)
    {
        return serialize($data);
    }

    /**
     * {@inheritDoc}
     */
    function unserialize($data, $class = null)
    {
        return unserialize($data);
    }
}