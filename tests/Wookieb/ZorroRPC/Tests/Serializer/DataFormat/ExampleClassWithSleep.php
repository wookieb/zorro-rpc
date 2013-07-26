<?php
namespace Wookieb\ZorroRPC\Tests\Serializer\DataFormat;

class ExampleClassWithSleep
{
    private $privateProperty;
    protected $protectedProperty;
    public $publicProperty;

    public function __construct($private = 'privateValue', $protected = 'protectedValue', $public = 'publicValue')
    {
        $this->privateProperty = $private;
        $this->protectedProperty = $protected;
        $this->publicProperty = $public;
    }

    public function __sleep()
    {
        return array('privateProperty', 'protectedProperty');
    }
}

