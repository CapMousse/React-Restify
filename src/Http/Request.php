<?php

namespace CapMousse\ReactRestify\Http;

use React\Http\Request as ReactHttpRequest;
use Evenement\EventEmitter;

class Request extends EventEmitter
{
    /** @var React\Http\Request */
    public $httpRequest;

    /** @var mixed */
    private $content;

    /** @var array */
    private $data = [];

    /**
     * @param ReactHttpRequest $httpRequest
     */
    public function __construct(ReactHttpRequest $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    /**
     * Set the raw data of the request
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Set the raw data of the request
     * @param mixed $content
     */
    public function getContent()
    {
        return $this->content;
    }

    public function getHeaders()
    {
        $headers = array_change_key_case($this->httpRequest->getHeaders(), CASE_LOWER);
        $headers = array_map(function ($value) {
            return strtolower($value);
        }, $headers);

        return $headers;
    }

    /**
     * Set the data array
     * @param array $data array of data
     */
    public function setData($data)
    {
        $this->data = array_merge($data, $this->data);
    }

    /**
     * Get the data array
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : false;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }
}
