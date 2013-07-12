<?php
require_once 'vendor/autoload.php';

$serializer = new \Wookieb\ZorroRPC\Serializer\SchemalessClientSerializer();
$serializer->setDefaultDataFormat(new \Wookieb\ZorroRPC\Serializer\DataFormat\JSONDataFormat());

$socket = new \ZMQSocket(new \ZMQContext, \ZMQ::SOCKET_REQ);
$socket->connect('tcp://0.0.0.0:8001');
$transport = new \Wookieb\ZorroRPC\Transport\ZeroMQ\ZeroMQClientTransport($socket);

$client = new \Wookieb\ZorroRPC\Client\Client($transport, $serializer);

var_dump($client->call('test', array('zia')));