<?php

namespace CapMousse\ReactRestify;

use React\Http\Request as HttpRequest;
use React\Http\Response as HttpResponse;
use CapMousse\ReactRestify\Container\Container;

class Server
{
    /**
     * Name of the server
     * @var string
     */
    public $name = "ReactRestify";

    /**
     * Version of the API
     * @var null
     */
    public $version = null;

    /**
     * @var Routing\Router
     */
    private $router;

    /**
     * @var string
     */
    private $allowOrigin = "*";

    /**
     * @param null $name
     * @param null $version
     */
    public function __construct($name = null, $version = null)
    {
        if (null !== $name) {
            $this->name = $name;
        }

        if (null !== $version) {
            $this->version = $version;
        }

        $this->router = new Routing\Router();

        $this->initEvents();
    }

    /**
     * Parse request from user
     *
     * @param \React\Http\Request  $httpRequest
     * @param \React\Http\Response $httpResponse
     */
    public function __invoke(HttpRequest $httpRequest, HttpResponse $httpResponse)
    {
        $request = new Http\Request($httpRequest);
        $response = new Http\Response($httpResponse, $this->name, $this->version);

        try {
            $this->router->launch($request, $response);
        } catch (\Exception $e) {
            $response
                ->setStatus(500)
                ->write($e->getMessage())
                ->end();
        }
    }

    /**
     * Create a new group of route
     * @param String   $prefix   prefix of the routes
     * @param Callable $callback
     *
     * @return \CapMousse\ReactRestify\Routing\Routes
     */
    public function group($prefix, $callback)
    {
        return $this->router->addGroup($prefix, $callback);
    }

    /**
     * The the Access-Control-Allow-Origin header
     *
     * @param string $origin
     */
    public function setAccessControlAllowOrigin($origin)
    {
        $this->allowOrigin = $origin;
    }

    /**
     * Init default event catch
     *
     * @return void
     */
    private function initEvents()
    {
        $this->router->on('NotFound', function($request, $response) {
            $response
                ->setStatus(404)
                ->write('Not found')
                ->end();
        });

        $this->router->on('error', function ($request, $response, $error) {
            $response
                ->reset()
                ->setStatus(500)
                ->write($error)
                ->end();
        });
    }

    /**
     * Manual router event manager
     * @param String          $event
     * @param Callable|string $callback
     */
    public function on($event, $callback)
    {
        $this->router->removeAllListeners($event);
        $this->router->on($event, $callback);
    }

    /**
     * Create runner instance
     * @param  Int    $port
     * @param  String $host
     * @return Server
     */
    public function listen($port, $host = "127.0.0.1")
    {
        $runner = new Runner($this);
        $runner->listen($port, $host);

        return $this;
    }

    /**
     * Server middleware
     * @param  Callable|string $callback
     */
    public function use($callback)
    {
        return $this->router->addMiddleware($callback);
    }

    /**
     * Add item to the container
     * @param string $alias
     * @param string|null $concrete
     * @return void
     */
    public function add($alias, $concrete = null)
    {
        $container = Container::getInstance();
        $container->add($alias, $concrete);
    }

    /**
     * @param string $name      method to call
     * @param array  $arguments
     */
    public function __call($name, $arguments)
    {
        return $this->router->addRoute($name, $arguments[0], $arguments[1]);
    }
}
