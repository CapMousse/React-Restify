<?php

namespace React\Restify;

use Evenement\EventEmitter;
use React\Http\Request;

class Router extends EventEmitter
{
    const PUT = 'PUT';
    const POST = 'POST';
    const GET = 'GET';
    const DELETE = 'DELETE';
    const CONSOLE = 'CONSOLE';

    /**
     * The current routes list
     * @var array
     */
    public $routes = array();

    /**
     * The routes list parsed to regexp
     * @var array
     */
    private $_parsed_routes = array();

    /**
     * The default route
     * @var mixed
     */
    private $_default = false;

    /**
     * The list of authorized patterns in a route
     * @var array
     */
    private $_authorized_patterns = array(
        ':any'      => '.+',
        ':slug'     => '[a-zA-Z0-9\/_-]+',
        ':alpha'    => '[a-zA-Z]+',
        ':num'      => '[0-9]+'
    );

    /**
     * The current asked uri
     * @var string|boolean
     */
    private $_uri = false;

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
     * @return Boolean
     */
    public function addRoutes($routes)
    {
        if (!is_array($routes)) {
            throw new \InvalidArgumentException("Routes must be an array");
        }

        $this->routes = array_merge($this->routes, $routes);

        return true;
    }

    /**
     * Add a new route
     *
     * @param string $route    uri to catch
     * @param mixed  $callback code to execute
     */
    public function addRoute($route, $callback)
    {
        if (!isset($this->routes[$route])) {
           $this->routes[$route] = array();
        }

        $this->routes[$route][] = $callback;
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
    public function launch(Request $request, Response $response){
        if (count($this->routes) === 0) {
            throw new \RuntimeException("No routes defined");
        }

        if (isset($this->routes['/'])) {
            $this->_default = $this->routes['/'];
            unset($this->routes['/']);
        }

        $this->parseRoutes();

        $this->_uri = $request->getPath();
        return $this->matchRoutes($request, $response);
    }
    /**
     * Parse all routes for uri matching
     *
     * @return boolean
     */
    private function parseRoutes()
    {
        # transform routes into usable routes for the router
        # thanks to Taluu (Baptiste Clavié) for the help

        foreach ($this->routes as $key => $value) {
            $key = preg_replace('#\[([a-zA-Z0-9]+)\]:([a-z]+)#', '(?<$1>:$2)', rtrim($key, '/'));
            $key = str_replace(array_keys($this->_authorized_patterns), array_values($this->_authorized_patterns), $key);
            $this->_parsed_routes[$key] = $value;
        }

        return true;
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
    private function matchRoutes(Request $request, Response $response){
        if (($this->_uri == null || empty($this->_uri)) && $this->_default !== false) {
            return $this->launchRoute($this->_default, $request, $response);
        }

        if (isset($this->routes[$this->_uri])) {
            return $this->launchRoute($this->routes[$this->_uri], $request, $response);
        }

        foreach ($this->_parsed_routes as $route => $val) {
            if (preg_match('#'.$route.'$#', $this->_uri, $array)) {

                $method_args = array();

                foreach ($array as $name => $value) {
                    if (!is_int($name)) {
                      $method_args[$name] = $value;
                    }
                }

                return $this->launchRoute($this->_parsed_routes[$route], $request, $response, $method_args);
            }
        }

        if ($this->_uri === "/" && $this->_default) {
            return $this->launchRoute($this->_default, $request, $response);
        }

        return $this->emit('NotFound', array($this->_uri));
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
    private function launchRoute($action, Request $request, Response $response, $args = array())
    {
        if (is_array($action)) {
            foreach ($action as $sub_action) {
                $this->launchRoute($sub_action, $request, $response, $args);
            }

            return true;
        }


        if (is_string($action)) {
            $action = explode(':', $action);
            $action[0] = new $action[0]();
        }

        call_user_func_array($action, array($request, $response, $args));

        return true;
    }
}
