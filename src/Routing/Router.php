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

    private $group;

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
        if (!empty($this->group)) {
            $route = implode('/', $this->group) . '/' . $route;
        }

        return $this->routes[] = new Route($method, $route, $callback);
    }

    /**
     * Create a new group of routes
     * @param  String $prefix prefix of thes routes
     * @return void
     */
    public function openGroup($prefix)
    {
        if (empty($this->group)) {
            $this->group = [$prefix];
        } else {
            $this->group[] = $prefix;
        }
    }

    /**
     * Close the last opened group
     * @return void
     */
    public function closeGroup()
    {
        array_pop($this->group);
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

                return $this->launchRoute($route, $request, $response, $next);
            }
        }

        if ($badMethod) {
            return $this->emit('MethodNotAllowed', array($request, $response, $next));
        }

        return $this->emit('NotFound', array($request, $response, $next));
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
            $headers = $request->httpRequest->getHeaders();

            //Get data chunck by chunk
            $request->httpRequest->on('data', function($data) use ($headers, $request, &$dataResult) {
                $dataResult .= $data;

                if (isset($headers["Content-Length"])) {
                    if(strlen($dataResult) == $headers["Content-Length"]) {
                        $request->httpRequest->close();
                    }
                } else {
                    $request->httpRequest->close();
                }
            });

            //Wait request end to launch route
            $request->httpRequest->on('end', function() use ($route, $action, $request, $response, $next, &$dataResult){
                parse_str($dataResult, $data);
                $request->setData($data);

                call_user_func_array($action, array($request, $response, $next));
                $route->emit('after', [$request, $response]);
            });
        } else {
            call_user_func_array($action, array($request, $response, $next));
            $route->emit('after', [$request, $response]);
        }
    }
}
