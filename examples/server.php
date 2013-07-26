<?php

require_once '../vendor/autoload.php';
require_once './ExampleClass.php';
use Wookieb\ZorroRPC\Server\Server;
use Wookieb\ZorroRPC\Transport\ZeroMQ\ZeroMQServerTransport;
use Wookieb\ZorroRPC\Serializer\SchemalessServerSerializer;
use Wookieb\ZorroRPC\Serializer\DataFormat\JSONDataFormat;
use Wookieb\ZorroRPC\Serializer\DataFormat\PHPNativeSerializerDataFormat;
use Wookieb\ZorroRPC\Headers\Headers;
use Wookieb\ZorroRPC\Server\MethodTypes;


$transport = ZeroMQServerTransport::create('tcp://0.0.0.0:1500');
$serializer = new SchemalessServerSerializer(new JSONDataFormat());

$server = new Server($transport, $serializer);

// uncomment these lines if u would like to use php serialization format
/*
$serializer->registerDataFormat(new PHPNativeSerializerDataFormat());
$server->setDefaultHeaders(new Headers(array(
    'content-type' => 'application/vnd.php.serialized'
)));
*/

$server->registerMethod('basic', function ($what) {
    return 'hello '.$what;
});

$server->registerMethod('serializationTest', function ($private = 'private', $protected = 'protected', $public = 'public') {
    $example = new \ExampleClass($private, $protected, $public);
    return $example;
});

$server->registerMethod('push', function ($data, \Closure $callback) {
    if (!$data) {
        throw new \InvalidArgumentException('Data cannot be empty');
    }
    $callback();
    file_put_contents('queue.log', $data.PHP_EOL, FILE_APPEND);
}, MethodTypes::PUSH);

$server->registerMethod('oneWay', function ($data) {
    file_put_contents('one_way.log', $data.PHP_EOL, FILE_APPEND);
}, MethodTypes::ONE_WAY);

$server->run();