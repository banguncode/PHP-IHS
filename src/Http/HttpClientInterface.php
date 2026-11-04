<?php

namespace BangunCode\Ihs\Http;

use BangunCode\Ihs\Exception\TransportException;

/**
 * Sends HTTP requests. Implement this to plug in another HTTP stack or a test double.
 */
interface HttpClientInterface
{
    /**
     * Sends a request and returns the response for every HTTP status, including 4xx and 5xx.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws TransportException When no response was received.
     */
    public function send(Request $request);
}
