<?php

namespace BangunCode\Ihs\Fhir;

use BangunCode\Ihs\Exception\InvalidArgumentException;

/**
 * Builder for a Bundle of type transaction, the format used to send a whole encounter in one request.
 *
 * Entries reference each other through their fullUrl:
 *
 * <code>
 * $bundle = new TransactionBundle();
 * $encounter = $bundle->create($encounterResource);
 * $bundle->create(array(
 *     'resourceType' => 'Condition',
 *     'encounter' => array('reference' => $encounter),
 * ));
 * $result = $client->transaction($bundle);
 * </code>
 */
final class TransactionBundle implements \JsonSerializable, \Countable
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private $entries = array();

    /**
     * Adds a POST entry.
     *
     * @param array|string|\JsonSerializable $resource
     * @param string|null                    $fullUrl  A urn:uuid value or a bare UUID. Generated when omitted.
     *
     * @return string The entry fullUrl, usable as a reference in other entries.
     */
    public function create($resource, $fullUrl = null)
    {
        $resource = $this->resource($resource);

        return $this->push($resource, 'POST', $resource['resourceType'], $fullUrl);
    }

    /**
     * Adds a PUT entry for a resource that already exists on the server.
     *
     * @param array|string|\JsonSerializable $resource Must contain resourceType and id.
     * @param string|null                    $fullUrl
     *
     * @return string The entry fullUrl.
     */
    public function update($resource, $fullUrl = null)
    {
        $resource = $this->resource($resource);
        if (empty($resource['id'])) {
            throw new InvalidArgumentException('A PUT entry needs a resource id.');
        }

        return $this->push($resource, 'PUT', $resource['resourceType'] . '/' . $resource['id'], $fullUrl);
    }

    /**
     * Adds a prebuilt entry as is.
     *
     * @param array<string, mixed> $entry
     *
     * @return TransactionBundle
     */
    public function addEntry(array $entry)
    {
        if (!isset($entry['request']['method'], $entry['request']['url'])) {
            throw new InvalidArgumentException('A transaction entry needs request.method and request.url.');
        }
        $this->entries[] = $entry;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray()
    {
        return array(
            'resourceType' => 'Bundle',
            'type' => 'transaction',
            'entry' => $this->entries,
        );
    }

    /**
     * @return array<string, mixed>
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->entries);
    }

    /**
     * @param array|string|\JsonSerializable $resource
     *
     * @return array<string, mixed>
     */
    private function resource($resource)
    {
        $resource = Payload::toArray($resource);
        if (empty($resource['resourceType']) || !is_string($resource['resourceType'])) {
            throw new InvalidArgumentException('A transaction entry resource needs a resourceType.');
        }

        return $resource;
    }

    /**
     * @param array<string, mixed> $resource
     * @param string               $method
     * @param string               $url
     * @param string|null          $fullUrl
     *
     * @return string
     */
    private function push(array $resource, $method, $url, $fullUrl)
    {
        if ($fullUrl === null) {
            $fullUrl = Uuid::urn();
        } elseif (strpos($fullUrl, 'urn:uuid:') !== 0 && Uuid::isValid($fullUrl)) {
            $fullUrl = 'urn:uuid:' . $fullUrl;
        }

        $this->entries[] = array(
            'fullUrl' => $fullUrl,
            'resource' => $resource,
            'request' => array('method' => $method, 'url' => $url),
        );

        return $fullUrl;
    }
}
