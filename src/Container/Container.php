<?php

namespace CapMousse\ReactRestify\Container;

use Psr\Container\ContainerInterface;
use CapMousse\ReactRestify\Errors\Container\ContainerException;
use CapMousse\ReactRestify\Errors\Container\NotFoundException;

class Container implements ContainerInterface
{
    private static $instance;

    /**
     * Resolved types
     * @var array
     */
    protected $definitions = [];

    /**
     * Globally available container
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static;
        }

        return self::$instance;
    }

    /**
     * Add item to the container
     * @param string $id
     * @param mixed|null $concrete
     */
    public function add($id, $concrete = null)
    {
        if (!is_string($id)) {
            throw new ContainerException('$id must be a string');
        }

        if (isset($this->definitions[$id])) {
            return;
        }

        if (is_null($concrete)) {
            $concrete = $id;
        }

        $this->definitions[$id] = $this->build($concrete);
    }

    /**
     * Check if item is available in container
     * @param  string  $id
     * @return boolean
     */
    public function has($id)
    {
        if (!is_string($id)) {
            throw new ContainerException('$id must be a string');
        }

        if (array_key_exists($id, $this->definitions)) {
            return true;
        }

        return false;
    }

    /**
     * Get an item of the container
     * @param  string $id
     * @return mixed
     * @throw  NotFoundException
     */
    public function get($id)
    {
        if (!is_string($id)) {
            throw new ContainerException('$id must be a string');
        }

        if (!isset($this->definitions[$id])) {
            throw new NotFoundException("Unresolvable ".$id);
        }

        return $this->definitions[$id];
    }

    /**
     * Call method with given parameters
     * @param  string|callable  $action
     * @param  array            $args
     * @return mixed
     */
    public function call ($action, array $args = [])
    {
        $method = $action;
        $class = null;

        if (is_string($action)) {
            list($class, $method) = explode('@', $action);
        }

        $reflection = $this->getActionReflection($method, $class);
        $args       = $this->getParametersDictionary($args);
        $parameters = $this->getParameters($reflection, $args);

        if (is_callable($method)) {
            return $method(...$parameters);
        }

        $class = $this->build($class);
        return $class->{$method}(...$parameters);
    }

    /**
     * Get reflection from an action
     * @param string      $method
     * @param string|null $class
     * @return callable
     */
    public function getActionReflection($method, $class = null)
    {
        if(!is_null($class)) {
            return new \ReflectionMethod($class, $method);
        }

        return new \ReflectionFunction($method);
    }

    /**
     * Find object and set classname alias on argument list
     * @param  array  $args
     * @return array
     */
    public function getParametersDictionary(array $args = [])
    {
        $dictionary = [];

        foreach ($args as $parameter) {
            if (!is_object($parameter)) continue;
            $dictionary[get_class($parameter)] = $parameter;
        }

        return array_merge($args, $dictionary);
    }

    /**
     * Get reflection parameters
     * @param  \ReflectionFunctionAbstract $reflection
     * @param  array                       $args
     * @return array
     */
    public function getParameters(\ReflectionFunctionAbstract $reflection, array $args = [])
    {
        $dependencies = $reflection->getParameters();
        $parameters = [];

        foreach ($dependencies as $parameter) {
            $parameters[] = $this->getParameter($parameter, $args);
        }

        return $parameters;
    }

    /**
     * Get paremeter value
     * @param  \ReflectionParameter $parameter 
     * @param  array                $args      
     * @return mixed
     */
    public function getParameter(\ReflectionParameter $parameter, array $args = [])
    {
        $class = $parameter->getClass();

        if ($class && $this->has($class->name)) {
            return $this->get($class->name);
        }

        if ($class && array_key_exists($class->name, $args)) {
            return $args[$class->name];
        }

        if (array_key_exists($parameter->name, $args)) {
            return $args[$parameter->name];
        }

        throw new NotFoundException("Unresolvable " . ($class ? $class->name : $parameter->name));
    }

    /**
     * Create a new container for asked class
     * @param  string $class
     * @return Container
     */
    public function build ($class)
    {
        $reflection = new \ReflectionClass($class);
        $parameters = [];

        if (!is_null($reflection->getConstructor())) {
            $parameters = $this->getParameters($reflection->getConstructor());
        }

        return new $class(...$parameters);
    }
}