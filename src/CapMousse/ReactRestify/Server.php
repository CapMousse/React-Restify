<?php

namespace CapMousse\ReactRestify;

use React\Http\Request as HttpRequest;
use React\Http\Response as HttpResponse;

use Evenement\EventEmitter;

class Server extends EventEmitter
{
    /**
     * Name of the server
     * @var string
     */
    public $name = "React/Restify";

    /**
     * Version of the API
     * @var null
     */
    public $version = null;

    /**
     * @var \React\Restify\Router
     */
    private $router;

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

        $response = new Response($HttpResponse, $this->name, $this->version);

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
        $response->addHeader("Access-Control-Request-Method", "POST, GET, PUT, DEL");
        $response->addHeader("Access-Control-Allow-Origin", $this->allowOrigin);

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
        $this->addRoute("POST", $route, $callback);

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
        $this->addRoute("GET", $route, $callback);

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
        $this->addRoute("DEL", $route, $callback);

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
        $this->addRoute("PUT", $route, $callback);

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
        $routeCallback = function(HttpRequest $request, Response $response, $args) use ($callback, $type) {
            $method = strtoupper($request->getMethod());

            if ($method !== $type) {
                return;
            }

            if (in_array($method, array('PUT', 'POST'))) {
                $dataResult = "";

                $request->on('data', function($data) use (&$dataResult) {
                    $dataResult .= $data;
                });

                $request->on('close', function() use ($callback, &$request, &$response, $args, &$dataResult){
                    parse_str($dataResult, $data);
                    $args = array_merge($args, $data);
                    call_user_func_array($callback, array($request, $response, $args));
                });
            } else {
                call_user_func_array($callback, array($request, $response, $args));
            }

        };

        $this->router->addRoute($route, $routeCallback);

        return $this;
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
}