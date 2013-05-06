<?php
require_once '../vendor/autoload.php';

use Wookieb\ZorroRPC\Server\Server;
use Wookieb\ZorroRPC\Server\AutoDiscover\BasicAutoDiscover;

class Service
{
    public function basicRPCMethod()
    {
        return 'world!';
    }
}

$autodiscover = new BasicAutoDiscover(new Service);
$server = Server::create('tcp://0.0.0.0:8000');
$server->registerMethods($autodiscover->discover());

$server->run();