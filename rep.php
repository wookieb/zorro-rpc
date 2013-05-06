<?php
$socket = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_REP);
$socket->bind('tcp://0.0.0.0:4242');
var_dump($socket->recv());
sleep(100);