<?php

namespace CapMousse\ReactRestify\Http;

use React\Http\Request as ReactHttpRequest;
use Evenement\EventEmitter;

class Request extends EventEmitter
{
    public $httpRequest;
    private $data = [];

    public function __construct(ReactHttpRequest $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    public function setData($data)
    {
        $this->data = array_merge($data, $this->data);
    }

    public function getData()
    {
        return $this->data;
    }

    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : false;
    }
}
