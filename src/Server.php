<?php

namespace CapMousse\ReactRestify;

use React\Http\Request as HttpRequest;
use React\Http\Response as HttpResponse;
use CapMousse\ReactRestify\Evenement\EventEmitter;

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
     * @var \React\Restify\Router
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
     * @param \React\Http\Request $HttpRequest
     * @param \React\Http\Response $HttpResponse
     */
    public function __invoke(HttpRequest $HttpRequest, HttpResponse $HttpResponse)
    {
        $start = microtime(true);

        $request = new Http\Request($HttpRequest);
        $response = new Http\Response($HttpResponse, $this->name, $this->version);

        try{
            $this->router->launch($request, $response, function() use ($request, $response, $start){
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
     * Create a new group of route
     * @param  String   $prefix   prefix of the routes
     * @param  Callable $callback
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
        $this->router->on('NotFound', function($request, $response, $next){
            $response->write('Not found');
            $response->setStatus(404);

            $next();
        });


        $this->router->on('MethodNotAllowed', function($request, $response, $next){
            $response->write('Method Not Allowed');
            $response->setStatus(405);

            $next();
        });
    }

    /**
     * Manual router event manager
     * @param  String   $event    
     * @param  Callable $callback
     */
    public function on($event, $callback)
    {
        $this->router->removeAllListeners($event);
        $this->router->on($event, $callback);
    }

    /**
     * @param  string $name      method to call
     * @param  array  $arguments
     */
    public function __call($name, $arguments)
    {
        return $this->router->addRoute($name, $arguments[0] $arguments[1]);
    }
}
