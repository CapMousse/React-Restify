<?php

namespace React\Restify;

use React\EventLoop\Factory;
use React\Socket\Server as SocketServer;
use React\Http\Server as HttpServer;
use React\Http\Request as HttpRequest;
use React\Http\Response as HttpResponse;

use Evenement\EventEmitter;

class Server extends EventEmitter
{
    /**
     * Name of the server
     * @var string
     */
    public static $name = "React-Restify Server";

    /**
     * Version of the API
     * @var null
     */
    public static $version = null;

    /**
     * @var \React\EventLoop\Factory
     */
    private $loop;

    /**
     * @var \React\Socket\Server
     */
    private $socket;

    /**
     * @var \React\Http\Server
     */
    private $http;

    /**
     * @var \React\Restify\Router
     */
    private $router;

    /**
     * The defined routes
     * @var array
     */
    private $routes = array();

    /**
     * @param null $name
     * @param null $version
     */
    public function __construct($name = null, $version = null)
    {
        if (null != $name) {
            self::$name = $name;
        }

        if (null != $version) {
            self::$version = $version;
        }


        $this->loop = Factory::create();
        $this->socket = new SocketServer($this->loop);
        $this->http = new HttpServer($this->socket, $this->loop);
        $this->router = new Router();
    }

    /**
     * Launch the server at the given port and host
     *
     * @param string $port
     * @param string $host
     */
    public function listen($port, $host = '127.0.0.1')
    {
        $this->router->addRoutes($this->routes);

        $this->http->on('request', array($this, 'parseRequest'));
        echo("Server running on {$host}:{$port}\n");

        $this->socket->listen($port, $host);
        $this->loop->run();
    }

    /**
     * Parse request from user
     *
     * @param \React\Http\Request $HttpRequest
     * @param \React\Http\Response $HttpResponse
     */
    public function parseRequest(HttpRequest $HttpRequest, HttpResponse $HttpResponse)
    {
        $start = microtime(true);

        $response = new Response($HttpResponse);

        $this->emit('parseRequest', array($HttpRequest, $response));

        try{
            $this->router->launch($HttpRequest, $response);
        }catch (\Exception $e){
            $response->write($e->getMessage());
            $response->setStatus(500);
            $response->end();
        }

        $end = microtime(true) - $start;

        $response->addHeader("X-Response-Time", $end);
        $response->addHeader("Date", date(DATE_RFC822));

        $response->end();
    }

    /**
     * Add a post route
     *
     * @param string $route
     * @param mixed  $callback
     *
     * @return Server
     */
    public function post($route, $callback)
    {
        $this->addRoute("post", $route, $callback);

        return $this;
    }

    /**
     * Add a get route
     *
     * @param string $route
     * @param mixed  $callback
     *
     * @return Server
     */
    public function get($route, $callback)
    {
        $this->addRoute("get", $route, $callback);

        return $this;
    }

    /**
     * Add a del route
     *
     * @param string $route
     * @param mixed  $callback
     *
     * @return Server
     */
    public function del($route, $callback)
    {
        $this->addRoute("del", $route, $callback);

        return $this;
    }

    /**
     * Add a put route
     *
     * @param string $route
     * @param mixed  $callback
     *
     * @return Server
     */
    public function put($route, $callback)
    {
        $this->addRoute("put", $route, $callback);

        return $this;
    }

    /**
     * Add the asked type of route
     *
     * @param string $type     type of route
     * @param string $route    filtered route
     * @param mixed  $callback callback
     *
     * @return Server
     */
    public function addRoute($type, $route, $callback)
    {
        if(!isset($this->routes[$route])) {
            $this->routes[$route] = array();
        }

        $this->routes[$route][] = function(HttpRequest $request, Response $response, $args) use ($callback, $type) {
            if (strtolower($request->getMethod()) !== $type) {
                return;
            }

            call_user_func_array($callback, array($request, $response, $args));
        };

        return $this;
    }
}