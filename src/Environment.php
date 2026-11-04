<?php

namespace BangunCode\Ihs;

use BangunCode\Ihs\Exception\InvalidArgumentException;

/**
 * SATUSEHAT platform environments and their endpoint URLs.
 */
final class Environment
{
    const DEVELOPMENT = 'development';
    const STAGING = 'staging';
    const PRODUCTION = 'production';

    /**
     * @var array<string, string>
     */
    private static $hosts = array(
        self::DEVELOPMENT => 'https://api-satusehat-dev.dto.kemkes.go.id',
        self::STAGING => 'https://api-satusehat-stg.dto.kemkes.go.id',
        self::PRODUCTION => 'https://api-satusehat.kemkes.go.id',
    );

    private function __construct()
    {
    }

    /**
     * @return string[]
     */
    public static function all()
    {
        return array_keys(self::$hosts);
    }

    /**
     * @param string $environment
     *
     * @return string OAuth2 base URL, for example https://api-satusehat.kemkes.go.id/oauth2/v1
     */
    public static function authUrl($environment)
    {
        return self::host($environment) . '/oauth2/v1';
    }

    /**
     * @param string $environment
     *
     * @return string FHIR base URL, for example https://api-satusehat.kemkes.go.id/fhir-r4/v1
     */
    public static function fhirUrl($environment)
    {
        return self::host($environment) . '/fhir-r4/v1';
    }

    /**
     * @param string $environment
     *
     * @return string
     */
    private static function host($environment)
    {
        if (!isset(self::$hosts[$environment])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown environment "%s". Expected one of: %s.',
                $environment,
                implode(', ', self::all())
            ));
        }

        return self::$hosts[$environment];
    }
}
