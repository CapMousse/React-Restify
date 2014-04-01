<?php

require '../vendor/autoload.php';

$server = new CapMousse\ReactRestify\Server("HelloWorldServer", "0.0.0.1");

$server->get('/hello/[name]:any', function ($request, $response, $args) {
    $response->write("Hello ".$args['name']);
});

$runner = new CapMousse\ReactRestify\Runner($server);
$runner->listen("1337", "37.59.123.121");
