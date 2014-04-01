<?php

namespace CapMousse\ReactRestify\Routing;

class Route
{
    public $parsedRoute;
    public $method;
    public $action;

    private $uri;
    private $filters = array();

    public function __construct ($method, $uri, $action)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
    }

    public function where($param, $filter)
    {
        if (is_array($param)) {
            $this->filters = array_merge($this->filters, $param);
            return;
        }

        $this->filters[$param] = $filter;
    }

    public function parse()
    {
        preg_match_all("#\{(\w+)\}#", $this->uri, $params);
        $replace = array();

        foreach ($params[1] as $param) {
            $replace['{'.$param.'}'] = '(?<'.$param.'>'. (isset($this->filters[$param]) ? $this->filters[$param] : '[a-zA-Z+0-9-.]+') .')';
        }

        $this->parsed = str_replace(array_keys($replace), array_values($replace), $this->uri);
    }

    public function isParsed()
    {
        return !empty($this->parsed);
    }
}
