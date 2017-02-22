<?php

namespace CapMousse\ReactRestify;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Socket\Server as SocketServer;
use React\Socket\SecureServer as SecureSocketServer;
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
     * @param  array  $cert
     */
    public function listen($port, $host = '127.0.0.1', array $cert = [])
    {
        $loop = Factory::create();
        $this->register($loop, $port, $host, $cert);
        $loop->run();
    }

    /**
     * Setup socket for main loop
     * @param  LoopInterface $loop
     * @param  string $port
     * @param  string $host
     * @param  array  $cert
     */
    public function register(LoopInterface $loop, $port, $host = '127.0.0.1', array $cert = [])
    {
        $socket = new SocketServer("{$host}:{$port}", $loop);

        if (count($cert) > 0) {
            $socket = new SecureSocketServer($socket, $loop, $cert);
        }

        $http = new HttpServer($socket);
        $http->on('request', $this->app);
        
        echo("Server running on {$host}:{$port}\n");
        $loop->run();
    }
}
