<?php

namespace BangunCode\Ihs\Tests\Support;

use BangunCode\Ihs\Http\HttpClientInterface;
use BangunCode\Ihs\Http\Request;
use BangunCode\Ihs\Http\Response;

/**
 * Replays queued responses and records every request it receives.
 */
final class MockHttpClient implements HttpClientInterface
{
    /**
     * @var Response[]
     */
    private $queue = array();

    /**
     * @var Request[]
     */
    public $requests = array();

    /**
     * @var int
     */
    private $tokens = 0;

    /**
     * @param int          $status
     * @param array|string $body
     * @param array        $headers
     *
     * @return MockHttpClient
     */
    public function queue($status, $body = '', array $headers = array())
    {
        if (is_array($body)) {
            $body = json_encode($body);
            $headers = array_merge(array('Content-Type' => 'application/json'), $headers);
        }
        $this->queue[] = new Response($status, $headers, $body);

        return $this;
    }

    /**
     * @return MockHttpClient
     */
    public function queueToken()
    {
        $this->tokens++;

        return $this->queue(200, array('access_token' => 'token-' . $this->tokens, 'expires_in' => '14399'));
    }

    /**
     * @param string $name File name in tests/Fixtures/errors without extension.
     *
     * @return MockHttpClient
     */
    public function queueFixture($name)
    {
        $fixture = json_decode(file_get_contents(__DIR__ . '/../Fixtures/errors/' . $name . '.json'), true);

        return $this->queue($fixture['status'], $fixture['body'], array('Content-Type' => $fixture['content_type']));
    }

    /**
     * {@inheritdoc}
     */
    public function send(Request $request)
    {
        $this->requests[] = $request;
        if (!$this->queue) {
            throw new \LogicException('No response queued for ' . $request->getMethod() . ' ' . $request->getUrl());
        }

        return array_shift($this->queue);
    }

    /**
     * @return Request
     */
    public function lastRequest()
    {
        return $this->requests[count($this->requests) - 1];
    }
}
