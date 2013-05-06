<?php
require_once '../vendor/autoload.php';

use Wookieb\ZorroRPC\Client\Client;

$client = Client::create('tcp://0.0.0.0:8000');
echo 'hello '.$client->call('basicRPCMethod');