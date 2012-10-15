<?php

require '../vendor/autoload.php';

$server = new React\Restify\Server("HelloWorldServer", "0.0.0.1");

$server->get('/hello/[name]:any', function ($request, $response, $args) {
    $response->write("Hello ".$args['name']);
});

$runner = new React\Restify\Runner($server);
$runner->listen("1337");