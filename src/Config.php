<?php

namespace BangunCode\Ihs;

use BangunCode\Ihs\Exception\InvalidArgumentException;

/**
 * Connection settings for one SATUSEHAT organization.
 */
final class Config
{
    /**
     * @var array<string, mixed>
     */
    private static $defaults = array(
        'environment' => Environment::DEVELOPMENT,
        'client_id' => null,
        'client_secret' => null,
        'organization_id' => null,
        'auth_url' => null,
        'base_url' => null,
        'dicom_router_url' => null,
        'http' => array(),
    );

    /**
     * @var array<string, string>
     */
    private static $environmentVariables = array(
        'environment' => 'SATUSEHAT_ENV',
        'client_id' => 'SATUSEHAT_CLIENT_ID',
        'client_secret' => 'SATUSEHAT_CLIENT_SECRET',
        'organization_id' => 'SATUSEHAT_ORGANIZATION_ID',
        'auth_url' => 'SATUSEHAT_AUTH_URL',
        'base_url' => 'SATUSEHAT_BASE_URL',
        'dicom_router_url' => 'SATUSEHAT_DICOM_ROUTER_URL',
    );

    /**
     * @var array<string, mixed>
     */
    private $values;

    /**
     * Options:
     * client_id and client_secret (required), environment (development, staging or production,
     * default development), organization_id (the SATUSEHAT Organization id of the facility),
     * auth_url and base_url (override the URLs derived from environment), dicom_router_url
     * (base FHIR URL of the on premise DICOM Router, for example http://10.0.0.5:8080/fhir) and
     * http (options for CurlHttpClient).
     *
     * @param array<string, mixed> $options
     */
    public function __construct(array $options)
    {
        $unknown = array_diff(array_keys($options), array_keys(self::$defaults));
        if ($unknown) {
            throw new InvalidArgumentException('Unknown configuration option: ' . implode(', ', $unknown) . '.');
        }

        $values = array_merge(self::$defaults, $options);

        foreach (array('client_id', 'client_secret') as $required) {
            if (!is_string($values[$required]) || $values[$required] === '') {
                throw new InvalidArgumentException(sprintf('Configuration option "%s" is required.', $required));
            }
        }
        if (!is_array($values['http'])) {
            throw new InvalidArgumentException('Configuration option "http" must be an array.');
        }

        if ($values['auth_url'] === null) {
            $values['auth_url'] = Environment::authUrl($values['environment']);
        }
        if ($values['base_url'] === null) {
            $values['base_url'] = Environment::fhirUrl($values['environment']);
        }
        foreach (array('auth_url', 'base_url', 'dicom_router_url') as $url) {
            if ($values[$url] !== null) {
                $values[$url] = rtrim($values[$url], '/');
            }
        }

        $this->values = $values;
    }

    /**
     * Builds a configuration from SATUSEHAT_ENV, SATUSEHAT_CLIENT_ID, SATUSEHAT_CLIENT_SECRET,
     * SATUSEHAT_ORGANIZATION_ID, SATUSEHAT_AUTH_URL, SATUSEHAT_BASE_URL and
     * SATUSEHAT_DICOM_ROUTER_URL. Explicit options take precedence.
     *
     * @param array<string, mixed> $options
     *
     * @return Config
     */
    public static function fromEnvironment(array $options = array())
    {
        foreach (self::$environmentVariables as $option => $variable) {
            $value = getenv($variable);
            if (!array_key_exists($option, $options) && $value !== false && $value !== '') {
                $options[$option] = $value;
            }
        }

        return new self($options);
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->values['environment'];
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->values['client_id'];
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->values['client_secret'];
    }

    /**
     * @return string|null
     */
    public function getOrganizationId()
    {
        return $this->values['organization_id'];
    }

    /**
     * @return string
     */
    public function getAuthUrl()
    {
        return $this->values['auth_url'];
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->values['base_url'];
    }

    /**
     * @return string|null
     */
    public function getDicomRouterUrl()
    {
        return $this->values['dicom_router_url'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getHttpOptions()
    {
        return $this->values['http'];
    }
}
