<?php

namespace CapMousse\ReactRestify\Routing;

use CapMousse\ReactRestify\Evenement\EventEmitter;
use CapMousse\ReactRestify\Http\Request;
use CapMousse\ReactRestify\Http\Response;
use CapMousse\ReactRestify\Traits\WaterfallTrait;
use CapMousse\ReactRestify\Container\Container;

class Router extends EventEmitter
{
    use WaterfallTrait;
    /**
     * The current routes list
     * @var array
     */
    public $routes = [];

    /**
     * @var array
     */
    private $middlewares = [];

    /**
     * The current asked uri
     * @var string|boolean
     */
    private $uri = false;

    /**
     * Create a new routing element
     *
     * @param array $routes a route array
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($routes = [])
    {
        if (!is_array($routes)) {
            throw new \InvalidArgumentException("Routes must be an array");
        }

        $this->addRoutes($routes);
    }

    /**
     * Add routes
     *
     * @param array $routes a route array
     *
     * @throws \InvalidArgumentException
     * @return Void
     */
    public function addRoutes($routes)
    {
        if (!is_array($routes)) {
            throw new \InvalidArgumentException("Routes must be an array");
        }

        $routes = array_filter($routes, function ($route) {
            return is_a('Route', $route);
        });

        $this->routes = array_merge($this->routes, $routes);
    }

    /**
     * Add a new route
     *
     * @param String   $method   type of route
     * @param String   $route    uri to catch
     * @param Callable $callback
     */
    public function addRoute($method, $route, $callback)
    {
        return $this->routes[] = new Route(strtoupper($method), $route, $callback);
    }

    /**
     * Create a new group of routes
     *
     * @param String $prefix prefix of thes routes
     *
     * @return \CapMousse\ReactRestify\Routing\Group
     */
    public function addGroup($prefix, $callback)
    {
        $group = new Routes($this, $prefix, $callback);

        return $group;
    }

    /**
     * Add a middleware
     * @param string|Callable $callback
     */
    public function addMiddleware($callback)
    {
        $this->middlewares[] = function (Callable $next, Request $request, Response $response) use ($callback) {
            $container = Container::getInstance();
            $parameters = array_merge([
                "request"   => $request,
                "response"  => $response,
                "next"      => $next
            ], $request->getData());

            $container->call($callback, $parameters);
        };
    }

    /**
     * Launch the route parsing
     *
     * @param \React\Http\Request     $request
     * @param \React\Restify\Response $response
     *
     * @throws \RuntimeException
     * @return Void
     */
    public function launch(Request $request, Response $response)
    {
        if (count($this->routes) === 0) {
            throw new \RuntimeException("No routes defined");
        }

        $this->uri = $request->httpRequest->getPath();

        if ($this->uri = null) {
            $this->uri = "/";
        }

        $request->on('end', function () use (&$request, &$response) {
            $this->matchRoutes($request, $response);  
        });

        $request->on('error', function ($error) use (&$request, &$response) {
            $this->emit('error', [$request, $response, $error]);
        });

        $request->parseData();
    }

    /**
     * Try to match the current uri with all routes
     *
     *
     * @param \React\Http\Request     $request
     * @param \React\Restify\Response $response
     *
     * @throws \RuntimeException
     * @return Void
     */
    private function matchRoutes(Request $request, Response $response)
    {
        $stack  = [];
        $path   = $request->httpRequest->getPath();
        $method = $request->httpRequest->getMethod();

        foreach ($this->routes as $route) {
            if (!$route->match($path, $method)) {
                continue;
            }

            $methodArgs = $route->getArgs($path);
            $request->setData($methodArgs);

            $route->on('error', function (...$args) {
                $this->emit('error', $args);
            });

            $stack[] = function (...$params) use ($route) {
                $route->run(...$params);
            };
        }

        if (count($stack) == 0) {
            $this->emit('NotFound', array($request, $response));
            return;
        }

        $this->runStack($stack, $request, $response);
    }

    /**
     * Launch route stack
     * @param  array    $stack    
     * @param  Request  $request  
     * @param  Response $response 
     * @return void
     */
    protected function runStack(array $stack, Request $request, Response $response)
    {
        $stack[] = function () use ($response) {
            $response->end();
        };

        $finalStack = array_merge($this->middlewares, $stack);
        $this->waterfall($finalStack, [$request, $response]);
    }
}
