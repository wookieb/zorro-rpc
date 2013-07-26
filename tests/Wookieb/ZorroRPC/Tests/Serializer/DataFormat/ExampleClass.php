<?php
namespace Wookieb\ZorroRPC\Tests\Serializer\DataFormat;

class ExampleClass
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
}

