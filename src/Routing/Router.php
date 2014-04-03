<?php

namespace CapMousse\ReactRestify\Routing;

use CapMousse\ReactRestify\Evenement\EventEmitter;
use CapMousse\ReactRestify\Http\Request;
use CapMousse\ReactRestify\Http\Response;

class Router extends EventEmitter
{
    /**
     * The current routes list
     * @var array
     */
    public $routes = array();

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
     * @return Router
     */
    function __construct($routes = array())
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
        return $this->routes[] = new Route($method, $route, $callback);
    }

    /**
     * Create a new group of routes
     * 
     * @param  String $prefix prefix of thes routes
     * 
     * @return \CapMousse\ReactRestify\Routing\Group
     */
    public function addGroup($prefix, $callback)
    {
        $group = new Group($this, $prefix, $callback);
        $callback($group);

        return $group;
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
    public function launch(Request $request, Response $response, $next)
    {
        if (count($this->routes) === 0) {
            throw new \RuntimeException("No routes defined");
        }

        $this->uri = $request->httpRequest->getPath();

        if ($this->uri = null) {
            $this->uri = "/";
        }

        $this->matchRoutes($request, $response, $next);
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
    private function matchRoutes(Request $request, Response $response, $next)
    {
        $badMethod = false;

        foreach ($this->routes as $route) {
            if (!$route->isParsed()) {
                $route->parse();
            }

            if (preg_match('#'.$route->parsed.'$#', $request->httpRequest->getPath(), $array)) {
                if ($route->method != strtoupper($request->httpRequest->getMethod())) {
                    $badMethod = true;
                    continue;
                }

                $method_args = array();

                foreach ($array as $name => $value) {
                    if (!is_int($name)) {
                      $method_args[$name] = $value;
                    }
                }

                if (count($method_args) > 0) {
                    $request->setData($method_args);
                }

                $route->run($request, $response, $next);
                return;
            }
        }

        if ($badMethod) {
            $this->emit('MethodNotAllowed', array($request, $response, $next));
            return;
        }

        $this->emit('NotFound', array($request, $response, $next));
    }
}
