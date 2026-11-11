<?php

namespace BangunCode\Ihs\Service;

use BangunCode\Ihs\Client;

/**
 * Base for the non FHIR SATUSEHAT APIs that share the OAuth2 token of the Client.
 */
abstract class ApiService
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @param Client $client
     * @param string $baseUrl
     */
    public function __construct(Client $client, $baseUrl)
    {
        $this->client = $client;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * @param string                             $path
     * @param array<string, string|int|string[]> $query
     *
     * @return array|null
     */
    protected function get($path, array $query = array())
    {
        return $this->client->request('GET', $this->baseUrl . $path, null, $query);
    }

    /**
     * @param string                $path
     * @param array                 $body
     * @param array<string, string> $headers
     *
     * @return array|null
     */
    protected function post($path, array $body, array $headers = array())
    {
        return $this->client->request('POST', $this->baseUrl . $path, $body, array(), $headers);
    }
}
