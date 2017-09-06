<?php

namespace CapMousse\ReactRestify\Routing;

use CapMousse\ReactRestify\Evenement\EventEmitter;
use CapMousse\ReactRestify\Http\Request;
use CapMousse\ReactRestify\Http\Response;
use CapMousse\ReactRestify\Container\Container;
use CapMousse\ReactRestify\Traits\EventTrait;

class Route extends EventEmitter
{
    use EventTrait;

    /**
     * Regexp ready route
     * @var String
     */
    public $parsedRoute;

    /**
     * Route method type
     * @var String
     */
    public $method;

    /**
     * Route action
     * @var Callable
     */
    public $action;

    /**
     * Route uri
     * @var String
     */
    private $uri;

    /**
     * Route filters
     * @var array
     */
    private $filters = [];

    /**
     * @param String   $method
     * @param String   $uri
     * @param Callable $action
     */
    public function __construct ($method, $uri, $action)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
    }

    /**
     * Create a new filter for current route
     *
     * @param String|array $param  parameter to filter
     * @param String $filter regexp to execute
     *
     * @return void
     */
    public function where($param, $filter)
    {
        if (is_array($param)) {
            $this->filters = array_merge($this->filters, $param);

            return;
        }

        $this->filters[$param] = $filter;
    }

    /**
     * Parse route uri
     *
     * @return void
     */
    public function parse()
    {
        preg_match_all("#\{(\w+)\}#", $this->uri, $params);
        $replace = [];

        foreach ($params[1] as $param) {
            $replace['{'.$param.'}'] = '(?<'.$param.'>'. (isset($this->filters[$param]) ? $this->filters[$param] : '[a-zA-Z+0-9-.]+') .')';
        }

        $this->parsedRoute = str_replace(array_keys($replace), array_values($replace), $this->uri);
    }

    /**
     * Check if uri is parsed
     *
     * @return boolean
     */
    public function isParsed()
    {
        return !empty($this->parsedRoute);
    }

    /**
     * Check if path match route uri
     * @param  String $path
     * @param  String $method
     * @return bool
     */
    public function match($path, $method)
    {
        if (!$this->isParsed()) $this->parse();

        if (!preg_match('#'.$this->parsedRoute.'$#', $path)) return false;
        if (strtoupper($method) !== $this->method) return false;

        return true;
    }

    /**
     * Parse route arguments
     * @param  String $path 
     * @return array
     */
    public function getArgs($path)
    {
        if (!$this->isParsed()) $this->parse();
        
        $data = [];
        $args = [];
        preg_match('#'.$this->parsedRoute.'$#', $path, $data);


        foreach ($data as $name => $value) {
            if (is_int($name)) continue;
            $args[$name] = $value;
        }

        return $args;
    }

    /**
     * Run the current route
     *
     * @param Callable                $next
     * @param \React\Http\Request     $request
     * @param \React\Restify\Response $response
     *
     * @return Void
     */
    public function run(Callable $next, Request $request, Response $response)
    {
        $container  = Container::getInstance();
        $parameters = array_merge([
            "request"   => $request,
            "response"  => $response,
            "next"      => $next
        ], $request->getData());

        try {
            $container->call($this->action, $parameters);
            $this->emit('after', [$request, $response, $this]);
        } catch (\Exception $e) {
            $this->emit('error', [$request, $response, $e->getMessage()]);
        }
    }
}
