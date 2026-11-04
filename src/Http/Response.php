<?php

namespace BangunCode\Ihs\Http;

/**
 * Immutable HTTP response.
 */
final class Response
{
    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var array<string, string> Lower cased header names.
     */
    private $headers = array();

    /**
     * @var string
     */
    private $body;

    /**
     * @var bool
     */
    private $decoded = false;

    /**
     * @var mixed
     */
    private $json;

    /**
     * @param int                   $statusCode
     * @param array<string, string> $headers    Repeated headers may be joined with ", ".
     * @param string                $body
     */
    public function __construct($statusCode, array $headers = array(), $body = '')
    {
        $this->statusCode = (int) $statusCode;
        foreach ($headers as $name => $value) {
            $this->headers[strtolower($name)] = (string) $value;
        }
        $this->body = (string) $body;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
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
        $name = strtolower($name);

        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return mixed Decoded JSON body as associative arrays, or null when the body is not JSON.
     */
    public function getJson()
    {
        if (!$this->decoded) {
            $this->decoded = true;
            $this->json = $this->body === '' ? null : json_decode($this->body, true);
        }

        return $this->json;
    }
}
