<?php

namespace BangunCode\Ihs\Fhir;

/**
 * Read access to a Bundle returned by a search or a transaction.
 *
 * Iterating over a Bundle yields the resources of its entries.
 */
final class Bundle implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string, mixed>
     */
    private $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return string|null searchset, transaction-response, batch-response and so on.
     */
    public function getType()
    {
        return isset($this->data['type']) ? (string) $this->data['type'] : null;
    }

    /**
     * @return int|null Total number of matches reported by the server, independent of paging.
     */
    public function getTotal()
    {
        return isset($this->data['total']) ? (int) $this->data['total'] : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEntries()
    {
        return isset($this->data['entry']) ? array_values((array) $this->data['entry']) : array();
    }

    /**
     * @param string|null $resourceType Only return resources of this type, useful with _include.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getResources($resourceType = null)
    {
        $resources = array();
        foreach ($this->getEntries() as $entry) {
            if (!isset($entry['resource']) || !is_array($entry['resource'])) {
                continue;
            }
            $type = isset($entry['resource']['resourceType']) ? $entry['resource']['resourceType'] : null;
            if ($resourceType === null || $type === $resourceType) {
                $resources[] = $entry['resource'];
            }
        }

        return $resources;
    }

    /**
     * @param string|null $resourceType
     *
     * @return array<string, mixed>|null
     */
    public function first($resourceType = null)
    {
        $resources = $this->getResources($resourceType);

        return $resources ? $resources[0] : null;
    }

    /**
     * @param string $relation self, next, previous, first or last.
     *
     * @return string|null
     */
    public function getLink($relation)
    {
        foreach (isset($this->data['link']) ? (array) $this->data['link'] : array() as $link) {
            if (isset($link['relation'], $link['url']) && $link['relation'] === $relation) {
                return (string) $link['url'];
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function hasNextPage()
    {
        return $this->getLink('next') !== null;
    }

    /**
     * Entry responses of a transaction-response Bundle.
     *
     * @return array<int, array<string, mixed>> Each item has status (for example "201 Created"), location,
     *                                          etag, lastModified and, on SATUSEHAT, resourceType and resourceID.
     */
    public function getResponses()
    {
        $responses = array();
        foreach ($this->getEntries() as $entry) {
            if (isset($entry['response']) && is_array($entry['response'])) {
                $responses[] = $entry['response'];
            }
        }

        return $responses;
    }

    /**
     * Ids assigned by the server in a transaction-response, in entry order.
     *
     * @return array<int, array{resourceType: string|null, id: string|null, status: string|null}>
     */
    public function getCreatedIds()
    {
        $ids = array();
        foreach ($this->getResponses() as $response) {
            $type = isset($response['resourceType']) ? $response['resourceType'] : null;
            $id = isset($response['resourceID']) ? $response['resourceID'] : null;
            if (($type === null || $id === null) && isset($response['location'])
                && preg_match('#/([A-Za-z]+)/([^/]+)(/_history/[^/]+)?$#', $response['location'], $match)) {
                $type = $type === null ? $match[1] : $type;
                $id = $id === null ? $match[2] : $id;
            }
            $ids[] = array(
                'resourceType' => $type,
                'id' => $id,
                'status' => isset($response['status']) ? $response['status'] : null,
            );
        }

        return $ids;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @return \ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->getResources());
    }

    /**
     * @return int Number of entries in this page.
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->getEntries());
    }
}
