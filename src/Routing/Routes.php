<?php

namespace CapMousse\ReactRestify\Routing;

use CapMousse\ReactRestify\Evenement\EventEmitter;
use CapMousse\ReactRestify\Routing\ControllerContainer;
use CapMousse\ReactRestify\Traits\EventTrait;

class Routes extends EventEmitter
{
    use EventTrait;
    /**
     * Router instance
     * @var \CapMousse\ReactRestify\Routing\Router
     */
    private $router;

    /**
     * Group prefix
     * @var String
     */
    private $prefix;

    /**
     * Routes of the group
     * @var array
     */
    public $routes = [];

    /**
     * Create a new group
     *
     * @param \CapMousse\ReactRestify\Routing\Router $router
     * @param String                                 $prefix
     * @param Callable                               $callback
     */
    public function __construct($router, $prefix, $callback)
    {
        $this->router = $router;
        $this->prefix = $prefix;

        $callback($this);
    }

    /**
     * Create a new route for the group
     * @param String   $method
     * @param String   $route
     * @param Callable $callback
     */
    public function addRoute($method, $route, $callback)
    {
        $route = $this->router->addRoute(strtoupper($method), $this->prefix . '/' . $route, $callback);

        $route->onAny(function($event, $arguments){
            $this->emit($event, $arguments);
        });

        $this->routes[] = $route;

        return $route;
    }

    /**
     * Add a new group of routes
     * @param string   $prefix
     * @param Callable $callback
     *
     * return \CapMousse\ReactRestify\Routing\Group
     */
    public function group($prefix, $callback)
    {
        $group = $this->router->addGroup($this->prefix . '/' . $prefix, $callback);

        $group->onAny(function($event, $arguments){
            $this->emit($event, $arguments);
        });
    }

    /**
     * @param string $name      method to call
     * @param array  $arguments
     */
    public function __call($name, $arguments)
    {
        $arguments =  array_merge([$name], $arguments);

        return call_user_func_array(array($this, 'addRoute'), $arguments);
    }
}
