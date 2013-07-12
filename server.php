<?php
require_once 'vendor/autoload.php';
$socket = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_ROUTER);
$socket->bind('tcp://0.0.0.0:8001');

$msg = $socket->recvMulti();

print_r(array(
        $msg[0], '', $msg[1], '', 2, '', '"hello 1"'
));
$socket->sendMulti(array(
	$msg[0], $msg[1], '', 2, '', '"hello 1"'
));
die();

$serializer = new \Wookieb\ZorroRPC\Serializer\SchemalessServerSerializer();
$serializer->setDefaultDataFormat(new \Wookieb\ZorroRPC\Serializer\DataFormat\JSONDataFormat());

$socket = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_REP);
$socket->bind('tcp://0.0.0.0:8001');
$transport = new \Wookieb\ZorroRPC\Transport\ZeroMQ\ZeroMQServerTransport($socket);

$server = new \Wookieb\ZorroRPC\Server\Server($transport, $serializer);


$server->registerMethod('test', function($a) {
    return array('jest' => 'bardzo', 'spoko' => $a);
});

$server->run();
