<?php

namespace BangunCode\Ihs\Tests;

use BangunCode\Ihs\Config;
use BangunCode\Ihs\Environment;

final class ConfigTest extends TestCase
{
    public function testUrlsFollowEnvironment()
    {
        $production = $this->config(array('environment' => Environment::PRODUCTION));
        $staging = $this->config(array('environment' => Environment::STAGING));

        $this->assertSame('https://api-satusehat.kemkes.go.id/oauth2/v1', $production->getAuthUrl());
        $this->assertSame('https://api-satusehat.kemkes.go.id/fhir-r4/v1', $production->getBaseUrl());
        $this->assertSame('https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1', $staging->getBaseUrl());
        $this->assertSame('https://api-satusehat.kemkes.go.id', $production->getApiUrl());
        $this->assertSame('https://api-satusehat-stg.dto.kemkes.go.id/kptl-api-v1', $staging->getKptlUrl());
        $this->assertSame(Environment::DEVELOPMENT, $this->config()->getEnvironment());
    }

    public function testUrlOverridesAreTrimmed()
    {
        $config = $this->config(array('base_url' => 'https://proxy.local/fhir/', 'dicom_router_url' => 'http://10.0.0.5/fhir/'));

        $this->assertSame('https://proxy.local/fhir', $config->getBaseUrl());
        $this->assertSame('http://10.0.0.5/fhir', $config->getDicomRouterUrl());
    }

    public function testInvalidOptionsAreRejected()
    {
        $invalid = array(
            array('client_secret' => 'secret'),
            array('client_id' => 'client', 'client_secret' => ''),
            array('client_id' => 'client', 'client_secret' => 'secret', 'clientId' => 'typo'),
            array('client_id' => 'client', 'client_secret' => 'secret', 'environment' => 'sandbox'),
            array('client_id' => 'client', 'client_secret' => 'secret', 'http' => 'curl'),
        );

        foreach ($invalid as $options) {
            $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () use ($options) {
                new Config($options);
            });
        }
    }

    public function testFromEnvironment()
    {
        putenv('SATUSEHAT_CLIENT_ID=env-client');
        putenv('SATUSEHAT_CLIENT_SECRET=env-secret');
        putenv('SATUSEHAT_ENV=staging');

        $config = Config::fromEnvironment(array('organization_id' => '100'));

        putenv('SATUSEHAT_CLIENT_ID');
        putenv('SATUSEHAT_CLIENT_SECRET');
        putenv('SATUSEHAT_ENV');

        $this->assertSame('env-client', $config->getClientId());
        $this->assertSame('env-secret', $config->getClientSecret());
        $this->assertSame(Environment::STAGING, $config->getEnvironment());
        $this->assertSame('100', $config->getOrganizationId());
    }
}
