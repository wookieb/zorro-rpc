= ZorroRPC
Zorro RPC is RPC library based on ZeroMQ (REQ - REP topology) with msgpack serialization

== Examples

=== Server

```
use Wookieb\ZorroRPC\Server\Server;

$server = Server::create('tcp://0.0.0.0:8000');

function basicRPCMethod()
{
    return 'world!';
}

$server->registerMethod('basicRPCMethod', 'basicRPCMethod');
$server->run();
```

=== Client
```
use Wookieb\ZorroRPC\Client\Client;

$client = Client::create('tcp://0.0.0.0:8000');
echo 'hello '.$client->call('basicRPCMethod');
```
