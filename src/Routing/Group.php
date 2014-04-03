<?php

namespace CapMousse\ReactRestify\Routing;

use CapMousse\ReactRestify\Evenement\EventEmitter;

class Group extends EventEmitter
{
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
    public $routes = array();

    /**
     * Create a new group
     * 
     * @param  \CapMousse\ReactRestify\Routing\Router $router
     * @param  String                                 $prefix   
     * @param  Callable                               $callback
     */
    public function __construct($router, $prefix)
    {
        $this->router = $router;
        $this->prefix = $prefix;
    }

    public function addRoute($method, $route, $callback)
    {
        $route = $this->router->addRoute($method, $this->prefix . '/' . $route, $callback);

        $route->onAny(function($event, $arguments){
            $this->emit($event, $arguments);
        });

        $this->routes[] = $route;

        return $route;
    }

    /**
     * Add a new group of routes
     * @param  string   $prefix 
     * @param  Callable $callback
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
     * Helper to listen to after event
     * 
     * @param  Callable $callback
     * @return Void
     */
    public function after($callback)
    {
        $this->on('after', $callback);
    }

    /**
     * [__call description]
     * @param  string $name      method to call
     * @param  array  $arguments
     */
    public function __call($name, $arguments)
    {
        $arguments =  array_merge([$name], $arguments);
        return call_user_func_array(array($this, 'addRoute'), $arguments);
    }
}