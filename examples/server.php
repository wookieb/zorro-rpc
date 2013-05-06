<?php
require_once '../vendor/autoload.php';

use Wookieb\ZorroRPC\Server\Server;

$server = Server::create('tcp://0.0.0.0:8000');

function basicRPCMethod()
{
    return 'world!';
}

$server->registerMethod('basicRPCMethod', 'basicRPCMethod');
$server->run();