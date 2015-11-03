<?php

require '../vendor/autoload.php';

$server = new CapMousse\ReactRestify\Server("HelloWorldServer", "0.0.0.1");

$server->get('/hello/{name}', function ($request, $response, $next) {
    $response->write("Hello ".$request->name);
    $next();
});

$runner = new CapMousse\ReactRestify\Runner($server);
$runner->listen("1337");
