<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Client;
use BangunCode\Ihs\Exception\InvalidArgumentException;
use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\JsonPatch;
use BangunCode\Ihs\Fhir\Payload;

/**
 * RESTful operations on one FHIR resource type: create, read, update, patch and search.
 *
 * Resource payloads may be given as arrays, JSON strings or JsonSerializable objects.
 * Results are returned as decoded associative arrays.
 */
class ResourceEndpoint
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $type;

    /**
     * @param Client $client
     * @param string $type
     */
    public function __construct(Client $client, $type)
    {
        $this->client = $client;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * POST /{type}
     *
     * @param array|string|\JsonSerializable $resource resourceType is filled in when missing.
     *
     * @return array The created resource including its server assigned id.
     */
    public function create($resource)
    {
        return $this->client->request('POST', $this->type, $this->prepare($resource));
    }

    /**
     * GET /{type}/{id}
     *
     * @param string $id
     *
     * @return array
     */
    public function read($id)
    {
        return $this->client->request('GET', $this->path($id));
    }

    /**
     * PUT /{type}/{id}
     *
     * SATUSEHAT rejects a PUT whose payload id differs from the id in the URL
     * (OperationOutcome "resource_mismatch"), so the id is checked locally and filled in when missing.
     *
     * @param string                         $id
     * @param array|string|\JsonSerializable $resource
     *
     * @return array The updated resource.
     */
    public function update($id, $resource)
    {
        $resource = $this->prepare($resource);
        if (!isset($resource['id'])) {
            $resource['id'] = (string) $id;
        } elseif ((string) $resource['id'] !== (string) $id) {
            throw new InvalidArgumentException(sprintf(
                'Resource id "%s" does not match the id "%s" being updated.',
                $resource['id'],
                $id
            ));
        }

        return $this->client->request('PUT', $this->path($id), $resource);
    }

    /**
     * PATCH /{type}/{id} with a JSON Patch document.
     *
     * @param string                 $id
     * @param JsonPatch|array|string $operations
     *
     * @return array The patched resource.
     */
    public function patch($id, $operations)
    {
        $patch = JsonPatch::from($operations);
        if (count($patch) === 0) {
            throw new InvalidArgumentException('A JSON Patch needs at least one operation.');
        }

        return $this->client->request(
            'PATCH',
            $this->path($id),
            $patch->toArray(),
            array(),
            array('Content-Type' => 'application/json-patch+json')
        );
    }

    /**
     * GET /{type}?{parameters}
     *
     * @param array<string, string|int|string[]> $parameters An array value repeats the parameter.
     *
     * @return Bundle A searchset Bundle holding one page of results.
     */
    public function search(array $parameters = array())
    {
        return new Bundle((array) $this->client->request('GET', $this->type, null, $parameters));
    }

    /**
     * Iterates over every match across all result pages.
     *
     * @param array<string, string|int|string[]> $parameters
     *
     * @return \Generator|array[]
     */
    public function searchAll(array $parameters = array())
    {
        $bundle = $this->search($parameters);
        while ($bundle !== null) {
            foreach ($bundle->getResources($this->type) as $resource) {
                yield $resource;
            }
            $bundle = $bundle->hasNextPage() ? $this->client->nextPage($bundle) : null;
        }
    }

    /**
     * GET /{type}?identifier={system}|{value}
     *
     * @param string                             $system
     * @param string                             $value
     * @param array<string, string|int|string[]> $parameters Additional parameters.
     *
     * @return Bundle
     */
    public function searchByIdentifier($system, $value, array $parameters = array())
    {
        return $this->search(array_merge(array('identifier' => $system . '|' . $value), $parameters));
    }

    /**
     * GET /{type}/{id}/{operation}, an instance level FHIR operation such as $everything.
     *
     * @param string                             $id
     * @param string                             $operation  Operation name including the leading "$".
     * @param array<string, string|int|string[]> $parameters
     *
     * @return array|null
     */
    public function operation($id, $operation, array $parameters = array())
    {
        if (!is_string($operation) || !preg_match('/^\$[A-Za-z][A-Za-z-]*$/', $operation)) {
            throw new InvalidArgumentException(sprintf('Invalid operation name "%s".', $operation));
        }

        return $this->client->request('GET', $this->path($id) . '/' . $operation, null, $parameters);
    }

    /**
     * @param array|string|\JsonSerializable $resource
     *
     * @return array
     */
    protected function prepare($resource)
    {
        $resource = Payload::toArray($resource);
        if (!isset($resource['resourceType'])) {
            $resource = array_merge(array('resourceType' => $this->type), $resource);
        } elseif ($resource['resourceType'] !== $this->type) {
            throw new InvalidArgumentException(sprintf(
                'Expected a %s resource, %s given.',
                $this->type,
                is_scalar($resource['resourceType']) ? $resource['resourceType'] : gettype($resource['resourceType'])
            ));
        }

        return $resource;
    }

    /**
     * @param string $id
     *
     * @return string
     */
    protected function path($id)
    {
        $id = (string) $id;
        if ($id === '' || strpbrk($id, '/?#') !== false) {
            throw new InvalidArgumentException(sprintf('Invalid %s id "%s".', $this->type, $id));
        }

        return $this->type . '/' . rawurlencode($id);
    }
}
