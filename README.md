ZorroRPC
========


Zorro RPC is RPC library based on ZeroMQ (REQ - REP topology) with msgpack serialization
[![Build Status](https://travis-ci.org/wookieb/zorro-rpc.png?branch=master)](https://travis-ci.org/wookieb/zorro-rpc)

Examples
========

Server
-----

```php
use Wookieb\ZorroRPC\Server\Server;

$server = Server::create('tcp://0.0.0.0:8000');

function basicRPCMethod()
{
    return 'world!';
}

$server->registerMethod('basicRPCMethod', 'basicRPCMethod');
$server->run();
```

Client
------
```php
use Wookieb\ZorroRPC\Client\Client;

$client = Client::create('tcp://0.0.0.0:8000');
echo 'hello '.$client->call('basicRPCMethod');
```
