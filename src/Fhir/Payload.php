<?php

namespace BangunCode\Ihs\Fhir;

use BangunCode\Ihs\Exception\InvalidArgumentException;

/**
 * Converts the payload forms accepted by this library into arrays and JSON.
 *
 * Every method that takes a resource accepts an associative array, a JSON string (for example
 * a body copied from the SATUSEHAT Postman collection) or a JsonSerializable object.
 */
final class Payload
{
    private function __construct()
    {
    }

    /**
     * @param array|string|\JsonSerializable $payload
     *
     * @return array
     */
    public static function toArray($payload)
    {
        if ($payload instanceof \JsonSerializable) {
            $payload = $payload->jsonSerialize();
        }
        if (is_string($payload)) {
            $decoded = json_decode($payload);
            if (!is_object($decoded) && !is_array($decoded)) {
                throw new InvalidArgumentException('Payload is not valid JSON: ' . json_last_error_msg());
            }
            $payload = self::fromDecoded($decoded);
        }
        if (!is_array($payload)) {
            throw new InvalidArgumentException(sprintf(
                'Payload must be an array, a JSON string or JsonSerializable, %s given.',
                is_object($payload) ? get_class($payload) : gettype($payload)
            ));
        }

        return $payload;
    }

    /**
     * Converts json_decode() objects into arrays, keeping empty objects as stdClass so they are
     * encoded back as {} rather than [].
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private static function fromDecoded($value)
    {
        if (is_object($value)) {
            $properties = get_object_vars($value);
            if (!$properties) {
                return $value;
            }
            $value = $properties;
        }
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::fromDecoded($item);
            }
        }

        return $value;
    }

    /**
     * @param array $payload
     *
     * @return string
     */
    public static function encode(array $payload)
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new InvalidArgumentException('Payload cannot be encoded as JSON: ' . json_last_error_msg());
        }

        return $json;
    }
}
