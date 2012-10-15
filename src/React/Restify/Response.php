<?php

namespace React\Restify;

use React\Http\Response as HttpResponse;

class Response
{
    /**
     * @var \React\Http\Response
     */
    private $httpResponse;

    private $server;
    private $version;

    /**
     * Status code of the response
     * @var int
     */
    private $status = 200;

    /**
     * Array of headers to send
     * @var array
     */
    private $headers = array();

    /**
     * The content-length
     * @var int
     */
    private $contentLength = 0;

    /**
     * Data to send
     * @var string
     */
    private $data;

    /**
     * Check if headers are already sent
     * @var bool
     */
    private $headersSent = false;

    /**
     * Create a new Restify/Response object
     *
     * @param \React\Http\Response $response
     *
     */
    public function __construct(HttpResponse $response, $server = null, $version = null)
    {
        $this->httpResponse = $response;
        $this->server = $server;
        $this->version = $version;
    }

    /**
     * Add a header to the response
     *
     * @param string $name
     * @param string $value
     *
     * @return \React\Restify\Response
     */
    public function addHeader($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Set the status code of the response
     *
     * @param int $code
     *
     * @return \React\Restify\Response
     */
    public function setStatus($code)
    {
        $this->status = $code;

        return $this;
    }

    /**
     * is the response writable ?
     *
     * @return boolean
     */
    public function isWritable()
    {
        return $this->httpResponse->isWritable();
    }

    /**
     * Write a HTTP 100 (continue) header
     */
    public function writeContinue()
    {
        $this->httpResponse->writeContinue();
    }

    /**
     * Write data to the response
     *
     * @param string $data
     */
    public function write($data)
    {
        $this->contentLength += strlen($data);
        $this->data .= $data;
    }

    /**
     * Write json to the response
     *
     * @param mixed $data
     */
    public function writeJson($data)
    {
        $data = json_encode($data);

        $this->write($data);
        $this->addHeader("Content-Type", "application/json");
    }

    /**
     * End the connexion
     */
    public function end()
    {
        $this->sendHeaders();
        $this->httpResponse->write($this->data);
        $this->httpResponse->end();
    }

    /**
     * Close the connexion
     */
    public function close()
    {
        $this->sendHeaders();
        $this->httpResponse->write($this->data);
        $this->httpResponse->close();
    }

    /**
     * Send all headers to the response
     */
    public function sendHeaders()
    {
        if ($this->headersSent) {
            return;
        }

        if (!isset($this->headers["Content-Length"])) {
            $this->addHeader("Content-Length", $this->contentLength);

            if (null !== $this->server) {
                $this->addHeader("Server", $this->server);
            }

            if (null !== $this->version) {
                $this->addHeader("Server-Version", $this->version);
            }

            if (!isset($this->headers["Content-Type"])) {
                $this->addHeader("Content-Type", "text/plain");
            }
        }

        $this->httpResponse->writeHead($this->status, $this->headers);
        $this->headersSent = true;
    }
}
