<?php

namespace BangunCode\Ihs\Http;

/**
 * Immutable HTTP request.
 */
final class Request
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $url;

    /**
     * @var array<string, string>
     */
    private $headers;

    /**
     * @var string|null
     */
    private $body;

    /**
     * @param string                $method
     * @param string                $url
     * @param array<string, string> $headers
     * @param string|null           $body
     */
    public function __construct($method, $url, array $headers = array(), $body = null)
    {
        $this->method = strtoupper($method);
        $this->url = $url;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $name Case insensitive.
     *
     * @return string|null
     */
    public function getHeader($name)
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return Request
     */
    public function withHeader($name, $value)
    {
        $headers = array();
        foreach ($this->headers as $key => $existing) {
            if (strcasecmp($key, $name) !== 0) {
                $headers[$key] = $existing;
            }
        }
        $headers[$name] = $value;

        return new self($this->method, $this->url, $headers, $this->body);
    }
}
