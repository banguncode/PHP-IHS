<?php

namespace BangunCode\Ihs\Fhir;

/**
 * RFC 4122 version 4 UUIDs for Bundle fullUrl values and resource ids.
 */
final class Uuid
{
    private function __construct()
    {
    }

    /**
     * @return string For example 3f0c2a8e-6d1b-4c2f-9a57-1e2d3c4b5a69.
     */
    public static function v4()
    {
        $bytes = self::randomBytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * @return string urn:uuid:{v4}, the form SATUSEHAT expects for Bundle entry fullUrl.
     */
    public static function urn()
    {
        return 'urn:uuid:' . self::v4();
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public static function isValid($value)
    {
        return is_string($value)
            && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    private static function randomBytes($length)
    {
        if (function_exists('random_bytes')) {
            return random_bytes($length);
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            $strong = false;
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if ($bytes !== false && $strong) {
                return $bytes;
            }
        }
        $bytes = '';
        for ($i = 0; $i < $length; $i++) {
            $bytes .= chr(mt_rand(0, 255));
        }

        return $bytes;
    }
}
