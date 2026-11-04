<?php

namespace BangunCode\Ihs\Http;

use BangunCode\Ihs\Exception\InvalidArgumentException;
use BangunCode\Ihs\Exception\TransportException;

/**
 * HttpClientInterface implementation on top of ext-curl.
 *
 * One curl handle is reused across requests so connections to SATUSEHAT are kept alive.
 */
final class CurlHttpClient implements HttpClientInterface
{
    /**
     * @var array<string, mixed>
     */
    private $options = array(
        'timeout' => 30,
        'connect_timeout' => 10,
        'verify' => true,
        'proxy' => null,
        'curl' => array(),
    );

    /**
     * @var resource|object|null
     */
    private $handle;

    /**
     * Supported options:
     * timeout (int, seconds, default 30), connect_timeout (int, seconds, default 10),
     * verify (bool or path to a CA bundle, default true), proxy (string or null) and
     * curl (array of extra CURLOPT_* values applied last).
     *
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = array())
    {
        $unknown = array_diff(array_keys($options), array_keys($this->options));
        if ($unknown) {
            throw new InvalidArgumentException('Unknown HTTP client option: ' . implode(', ', $unknown) . '.');
        }
        $this->options = array_merge($this->options, $options);
    }

    public function __destruct()
    {
        if ($this->handle !== null && PHP_VERSION_ID < 80000) {
            curl_close($this->handle);
        }
        $this->handle = null;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Request $request)
    {
        if ($this->handle === null) {
            $this->handle = curl_init();
        } else {
            curl_reset($this->handle);
        }

        $headers = array();
        $headerLines = array();
        foreach ($request->getHeaders() as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $options = array(
            CURLOPT_URL => $request->getUrl(),
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => (int) $this->options['timeout'],
            CURLOPT_CONNECTTIMEOUT => (int) $this->options['connect_timeout'],
            CURLOPT_SSL_VERIFYPEER => $this->options['verify'] !== false,
            CURLOPT_SSL_VERIFYHOST => $this->options['verify'] !== false ? 2 : 0,
            CURLOPT_HEADERFUNCTION => function ($handle, $line) use (&$headers) {
                $length = strlen($line);
                if (strpos($line, 'HTTP/') === 0) {
                    $headers = array();
                } elseif (strpos($line, ':') !== false) {
                    list($name, $value) = explode(':', $line, 2);
                    $name = strtolower(trim($name));
                    $value = trim($value);
                    $headers[$name] = isset($headers[$name]) ? $headers[$name] . ', ' . $value : $value;
                }

                return $length;
            },
        );

        if ($request->getMethod() === 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
        }
        if ($request->getBody() !== null) {
            $options[CURLOPT_POSTFIELDS] = $request->getBody();
        }
        if (is_string($this->options['verify'])) {
            $options[CURLOPT_CAINFO] = $this->options['verify'];
        }
        if ($this->options['proxy'] !== null) {
            $options[CURLOPT_PROXY] = $this->options['proxy'];
        }
        foreach ($this->options['curl'] as $option => $value) {
            $options[$option] = $value;
        }

        curl_setopt_array($this->handle, $options);
        $body = curl_exec($this->handle);

        if ($body === false) {
            throw new TransportException(sprintf(
                '%s %s failed: %s (curl error %d)',
                $request->getMethod(),
                $request->getUrl(),
                curl_error($this->handle),
                curl_errno($this->handle)
            ));
        }

        return new Response(curl_getinfo($this->handle, CURLINFO_HTTP_CODE), $headers, $body);
    }
}
