<?php
require_once 'vendor/autoload.php';
use Wookieb\ZorroRPC\Server\Server;



$server = Server::create('tcp://0.0.0.0:8000');

$server->registerMethods($d);

$server->run();