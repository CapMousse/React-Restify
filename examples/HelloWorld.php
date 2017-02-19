<?php

require '../vendor/autoload.php';

$server = new CapMousse\ReactRestify\Server("HelloWorldServer", "0.0.0.1");

$server->get('/hello/{name}', function ($request, $response, $next) {
    $response->write("Hello ".$request->name);
    $next();
});

$server->get('/hello/{name}', function ($request, $response) {
    $response->write("\nWe hope you are well !");
    $response->end();
});

$server->listen(1337);
