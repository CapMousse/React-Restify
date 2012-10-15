<?php

namespace React\Restify;

use React\EventLoop\Factory;
use React\Socket\Server as SocketServer;
use React\Http\Server as HttpServer;

class Runner
{
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function listen($port, $host = '127.0.0.1')
    {
        $loop = Factory::create();
        $socket = new SocketServer($loop);
        $http = new HttpServer($socket, $loop);

        $http->on('request', $this->app);
        echo("Server running on {$host}:{$port}\n");

        $socket->listen($port, $host);
        $loop->run();
    }
}
