<?php

namespace React\Restify;

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
     * @var \React\Restify\Router
     */
    private $router;

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

        $this->router = new Router();
    }

    /**
     * Parse request from user
     *
     * @param \React\Http\Request $HttpRequest
     * @param \React\Http\Response $HttpResponse
     */
    public function __invoke(HttpRequest $HttpRequest, HttpResponse $HttpResponse)
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
        $routes = array();
        $routes[$route][] = function(HttpRequest $request, Response $response, $args) use ($callback, $type) {
            if (strtolower($request->getMethod()) !== $type) {
                return;
            }

            call_user_func_array($callback, array($request, $response, $args));
        };

        $this->router->addRoutes($routes);

        return $this;
    }
}