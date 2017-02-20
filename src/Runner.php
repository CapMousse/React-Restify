<?php

namespace CapMousse\ReactRestify;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Socket\Server as SocketServer;
use React\Http\Server as HttpServer;

class Runner
{
    private $app;

    /**
     * @param Server $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Listen to host:port
     * @param  string $port
     * @param  string $host
     */
    public function listen($port, $host = '127.0.0.1')
    {
        $loop = Factory::create();
        $this->register($loop, $port, $host);
        $loop->run();
    }

    /**
     * Setup socket for main loop
     * @param  LoopInterface $loop
     * @param  string $port
     * @param  string $host
     */
    public function register(LoopInterface $loop, $port, $host = '127.0.0.1')
    {
        $socket = new SocketServer($loop);
        $http = new HttpServer($socket);

        $http->on('request', $this->app);
        echo("Server running on {$host}:{$port}\n");

        $socket->listen($port, $host);
    }
}
