<?php

namespace CapMousse\ReactRestify\Routing;

use Evenement\EventEmitter;
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
     * @param string   $method   type of route
     * @param string   $route    uri to catch
     * @param function $callback
     */
    public function addRoute($method, $route, $callback)
    {
        return $this->routes[] = new Route($method, $route, $callback);
    }

    /**
     * Launch the route parsing
     *
     * @param \React\Http\Request     $request
     * @param \React\Restify\Response $response
     *
     * @throws \RuntimeException
     * @return mixed
     */
    public function launch(Request $request, Response $response, $next){
        if (count($this->routes) === 0) {
            throw new \RuntimeException("No routes defined");
        }

        $this->uri = $request->httpRequest->getPath();

        if ($this->uri = null) {
            $this->uri = "/";
        }

        return $this->matchRoutes($request, $response, $next);
    }

    /**
     * Try to match the current uri with all routes
     *
     *
     * @param \React\Http\Request     $request
     * @param \React\Restify\Response $response
     *
     * @throws \RuntimeException
     * @return mixed
     */
    private function matchRoutes(Request $request, Response $response, $next){
        foreach ($this->routes as $route) {
            if (!$route->isParsed()) {
                $route->parse();
            }

            if (preg_match('#'.$route->parsed.'$#', $request->httpRequest->getPath(), $array) && $route->method == strtoupper($request->httpRequest->getMethod())) {
                $method_args = array();

                foreach ($array as $name => $value) {
                    if (!is_int($name)) {
                      $method_args[$name] = $value;
                    }
                }

                if (count($method_args) > 0) {
                    $request->setData($method_args);
                }

                return $this->launchRoute($route, $request, $response, $next);
            }
        }

        return $this->emit('NotFound', array($this->uri));
    }

    /**
     * Launch the asked route
     *
     * @param mixed                   $action   the function/class to call
     * @param \React\Http\Request     $request
     * @param \React\Restify\Response $response
     * @param array                   $args     args of the route
     *
     * @return boolean
     */
    private function launchRoute(Route $route, Request $request, Response $response, $next)
    {
        $action = $route->action;

        if (is_string($action)) {
            $action = explode(':', $action);
            $action[0] = new $action[0]();
        }

        if (in_array($route->method, array('PUT', 'POST'))) {
            $dataResult = "";

            //Get data chunck by chunk
            $request->httpRequest->on('data', function($data) use (&$dataResult) {
                $dataResult .= $data;
            });

            //Wait request end to launch route
            $request->httpRequest->on('end', function() use ($action, $request, $response, $next, &$dataResult){
                parse_str($dataResult, $data);
                $request->setData($data);
                var_dump($data);
                call_user_func_array($action, array($request, $response, $next));
            });
        } else {
            call_user_func_array($action, array($request, $response, $next));
        }
    }
}
