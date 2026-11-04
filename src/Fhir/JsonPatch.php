<?php

namespace BangunCode\Ihs\Fhir;

use BangunCode\Ihs\Exception\InvalidArgumentException;

/**
 * Builder for RFC 6902 JSON Patch documents, sent with Content-Type application/json-patch+json.
 *
 * <code>
 * $patch = JsonPatch::create()
 *     ->add('/problem', array(array('reference' => 'Condition/' . $conditionId)))
 *     ->replace('/status', 'completed');
 * </code>
 */
final class JsonPatch implements \JsonSerializable, \Countable
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private $operations = array();

    /**
     * @return JsonPatch
     */
    public static function create()
    {
        return new self();
    }

    /**
     * @param array|string|JsonPatch $operations A JsonPatch, a list of operation arrays or its JSON string.
     *
     * @return JsonPatch
     */
    public static function from($operations)
    {
        if ($operations instanceof self) {
            return $operations;
        }

        $patch = new self();
        foreach (Payload::toArray($operations) as $operation) {
            if (!is_array($operation) || !isset($operation['op'], $operation['path'])) {
                throw new InvalidArgumentException('Every JSON Patch operation needs "op" and "path".');
            }
            $patch->operations[] = $operation;
        }

        return $patch;
    }

    /**
     * @param string $path
     * @param mixed  $value
     *
     * @return JsonPatch
     */
    public function add($path, $value)
    {
        return $this->push(array('op' => 'add', 'path' => $path, 'value' => $value));
    }

    /**
     * @param string $path
     * @param mixed  $value
     *
     * @return JsonPatch
     */
    public function replace($path, $value)
    {
        return $this->push(array('op' => 'replace', 'path' => $path, 'value' => $value));
    }

    /**
     * @param string $path
     *
     * @return JsonPatch
     */
    public function remove($path)
    {
        return $this->push(array('op' => 'remove', 'path' => $path));
    }

    /**
     * @param string $from
     * @param string $path
     *
     * @return JsonPatch
     */
    public function copy($from, $path)
    {
        return $this->push(array('op' => 'copy', 'from' => $from, 'path' => $path));
    }

    /**
     * @param string $from
     * @param string $path
     *
     * @return JsonPatch
     */
    public function move($from, $path)
    {
        return $this->push(array('op' => 'move', 'from' => $from, 'path' => $path));
    }

    /**
     * @param string $path
     * @param mixed  $value
     *
     * @return JsonPatch
     */
    public function test($path, $value)
    {
        return $this->push(array('op' => 'test', 'path' => $path, 'value' => $value));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray()
    {
        return $this->operations;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->operations;
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->operations);
    }

    /**
     * @param array<string, mixed> $operation
     *
     * @return JsonPatch
     */
    private function push(array $operation)
    {
        if (!is_string($operation['path']) || ($operation['path'] !== '' && $operation['path'][0] !== '/')) {
            throw new InvalidArgumentException(sprintf('JSON Patch path "%s" must start with "/".', $operation['path']));
        }
        $this->operations[] = $operation;

        return $this;
    }
}
