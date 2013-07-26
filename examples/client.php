<?php
require_once '../vendor/autoload.php';
require_once './ExampleClass.php';

use Wookieb\ZorroRPC\Client\Client;
use Wookieb\ZorroRPC\Transport\ZeroMQ\ZeroMQClientTransport;
use Wookieb\ZorroRPC\Serializer\SchemalessClientSerializer;
use Wookieb\ZorroRPC\Serializer\DataFormat\JSONDataFormat;
use Wookieb\ZorroRPC\Serializer\DataFormat\PHPNativeSerializerDataFormat;
use Wookieb\ZorroRPC\Headers\Headers;


$transport = ZeroMQClientTransport::create('tcp://0.0.0.0:1500');
$serializer = new SchemalessClientSerializer(new JSONDataFormat());

$client = new Client($transport, $serializer);

// uncomment these lines if u would like to use php serialization format
/*
 $serializer->registerDataFormat(new PHPNativeSerializerDataFormat());
$client->setDefaultHeaders(new Headers(array(
    'content-type' => 'application/vnd.php.serialized'
)));
*/
echo 'Basic'.PHP_EOL;
echo $client->call('basic', array('user')).PHP_EOL;

echo 'Serialization test'.PHP_EOL;
var_dump($client->call('serializationTest', array('test1', 'test2')));
echo PHP_EOL;

echo 'Push test'.PHP_EOL;
$client->push('push', array('push '.uniqid()));
echo 'Check queue.log file'.PHP_EOL;

echo 'One way call test'.PHP_EOL;

$client->oneWayCall('oneWay', array('oneWay '.uniqid()));
echo 'Check one_way.log file'.PHP_EOL;