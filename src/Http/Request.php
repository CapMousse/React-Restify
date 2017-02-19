<?php

namespace CapMousse\ReactRestify\Http;

use React\Http\Request as ReactHttpRequest;
use CapMousse\ReactRestify\Evenement\EventEmitter;

class Request extends EventEmitter
{
    /** @var \React\Http\Request */
    public $httpRequest;

    /** @var string */
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
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Set the raw data of the request
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get formated headers
     * @return array
     */
    public function getHeaders()
    {
        $headers = array_change_key_case($this->httpRequest->getHeaders(), CASE_LOWER);
        $headers = array_map(function ($value) {
            return strtolower($value);
        }, $headers);

        return $headers;
    }

    /**
     * Parse request data
     * @return void
     */
    public function parseData()
    {
        $headers = $this->getHeaders();

        if (!in_array($this->httpRequest->getMethod(), ['PUT', 'POST'])) {
            return $this->emit('end');
        }

        $this->httpRequest->on('data', function($data) use ($headers, &$dataResult) {
            $dataResult .= $data;

            if (isset($headers["Content-Length"])) {
                if (strlen($dataResult) == $headers["Content-Length"]) {
                    $this->httpRequest->close();
                }
            } else {
                $this->httpRequest->close();
            }
        });

        $this->httpRequest->on('end', function() use (&$dataResult) {
            $this->onEnd($dataResult);
        });
    }

    /**
     * On request end
     * @param  string $dataResult
     * @return void
     */
    private function onEnd($dataResult)
    {
        if ($dataResult === null) return $this->emit('end');

        if ($this->isJson()) $this->parseJson($dataResult);
        else $this->parseStr($dataResult);
    }

    /**
     * Parse querystring
     * @param  string $dataString
     * @return void
     */
    private function parseStr($dataString)
    {
        $data = [];
        parse_str($dataString, $data);

        $this->setContent($dataString);
        if(is_array($data)) $this->setData($data);

        $this->emit('end');
    }

    /**
     * Parse json string
     * @param  string $jsonString
     * @return void
     */
    private function parseJson($jsonString)
    {
        $jsonData = json_decode($jsonString, true);

        if ($jsonData === null) {
            $this->emit('error', [json_last_error_msg()]);
            return;
        }

        $this->setContent($jsonString);
        $this->setData($jsonData);
        $this->emit('end');
    }

    /**
     * Check if current request is a json request
     * @return boolean
     */
    public function isJson()
    {
        $headers = $this->getHeaders();

        return isset($headers['content-type']) && $headers['content-type'] == 'application/json';
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
