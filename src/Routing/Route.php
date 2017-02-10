<?php

namespace CapMousse\ReactRestify\Routing;

use CapMousse\ReactRestify\Evenement\EventEmitter;
use CapMousse\ReactRestify\Http\Request;
use CapMousse\ReactRestify\Http\Response;

class Route extends EventEmitter
{
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
    private $filters = array();

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
     * @param String $param  parameter to filter
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
     * Helper to listing to after event
     *
     * @param  Callable $callback
     * @return Void
     */
    public function after($callback)
    {
        $this->on('after', $callback);
    }

    /**
     * Parse route uri
     *
     * @return void
     */
    public function parse()
    {
        preg_match_all("#\{(\w+)\}#", $this->uri, $params);
        $replace = array();

        foreach ($params[1] as $param) {
            $replace['{'.$param.'}'] = '(?<'.$param.'>'. (isset($this->filters[$param]) ? $this->filters[$param] : '[a-zA-Z+0-9-.]+') .')';
        }

        $this->parsed = str_replace(array_keys($replace), array_values($replace), $this->uri);
    }

    /**
     * Check if uri is parsed
     *
     * @return boolean
     */
    public function isParsed()
    {
        return !empty($this->parsed);
    }

    /**
     * Run the current route
     *
     * @param \React\Http\Request     $request
     * @param \React\Restify\Response $response
     * @param Callable                $next
     *
     * @return Void
     */
    public function run(Request $request, Response $response, $next)
    {
        if (is_string($this->action)) {
            $this->action = explode(':', $this->action);
            $this->action[0] = new $action[0]();
        }

        if (!in_array($this->method, array('PUT', 'POST'))) {
            call_user_func_array($this->action, array($request, $response, $next));
            $this->emit('after', [$request, $response, $this]);

            return;
        }

        $dataResult = "";
        $headers = $request->getHeaders();

        //Get data chunck by chunk
        $request->httpRequest->on('data', function($data) use ($headers, $request, &$dataResult) {
            $dataResult .= $data;

            if (isset($headers["Content-Length"])) {
                if (strlen($dataResult) == $headers["Content-Length"]) {
                    $request->httpRequest->close();
                }
            } else {
                $request->httpRequest->close();
            }
        });

        //Wait request end to launch route
        $request->httpRequest->on('end', function() use ($headers, $request, $response, &$dataResult, $next) {
            $error = null;
            $data = [];

            if ($dataResult !== null) {
                if (isset($headers['content-type']) && $headers['content-type'] == 'application/json') {
                    $jsonData = json_decode($dataResult, true);

                    if ($jsonData === null) {
                        $error = json_last_error_msg();
                    } else {
                        $data = $jsonData;
                    }
                } else {
                    parse_str($dataResult, $data);
                }
            }

            $request->setContent($dataResult);
            $request->setData($data);

            if (null === $error) {
                call_user_func_array($this->action, array($request, $response, $next));
                $this->emit('after', [$request, $response, $this]);
            } else {
                $this->emit('error', [$request, $response, $error, $next]);
            }

        });
    }
}
