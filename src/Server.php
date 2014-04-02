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

        $this->router = new Routing\Router();
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

        $request = new Http\Request($HttpRequest);
        $response = new Http\Response($HttpResponse, $this->name, $this->version);

        $this->emit('parseRequest', array($HttpRequest, $response));

        try{
            $this->router->launch($request, $response, function() use (&$request, &$response, $start){
                $end = microtime(true) - $start;

                $response->addHeader("X-Response-Time", $end);
                $response->addHeader("Date", date(DATE_RFC822));
                $response->addHeader("Access-Control-Request-Method", "POST, GET, PUT, DEL");
                $response->addHeader("Access-Control-Allow-Origin", $this->allowOrigin);

                $response->end();
            });
        }catch (\Exception $e){
            $response->write($e->getMessage());
            $response->setStatus(500);
            $response->end();
        }
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
        $this->router->addRoute("POST", $route, $callback);

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
        $this->router->addRoute("GET", $route, $callback);

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
    public function delete($route, $callback)
    {
        $this->router->addRoute("DELETE", $route, $callback);

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
        $this->router->addRoute("PUT", $route, $callback);

        return $this;
    }

    /**
     * Create a new group of route
     * @param  string   $prefix   prefix of the routes
     * @param  function $callback
     */
    public function group($prefix, $callback)
    {
        $this->router->openGroup($prefix);
        $callback($this);
        $this->router->closeGroup();
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
