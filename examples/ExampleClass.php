<?php

/**
 * @author Łukasz Kużyński "wookieb" <lukasz.kuzynski@gmail.com>
 */
class ExampleClass
{
    private $privateProperty = 'private';
    protected $protectedProperty = 'protected';
    public $publicProperty = 'public';

    public function __construct($private = 'private', $protected = 'protected')
    {
        $this->privateProperty = $private;
        $this->protectedProperty = $protected;
    }
}