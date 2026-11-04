<?php

namespace BangunCode\Ihs\Tests;

use BangunCode\Ihs\Auth\AccessToken;
use BangunCode\Ihs\Auth\FileTokenStorage;
use BangunCode\Ihs\Auth\MemoryTokenStorage;
use BangunCode\Ihs\Auth\OAuthTokenProvider;
use BangunCode\Ihs\Tests\Support\MockHttpClient;

final class AuthTest extends TestCase
{
    public function testTokenRequestMatchesOAuthEndpoint()
    {
        $http = new MockHttpClient();
        $http->queueToken();

        $provider = new OAuthTokenProvider($this->config(), $http);

        $this->assertSame('token-1', $provider->getToken());
        $request = $http->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            'https://api-satusehat-dev.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials',
            $request->getUrl()
        );
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeader('Content-Type'));
        $this->assertSame('client_id=client&client_secret=secret', $request->getBody());
    }

    public function testCachedTokenIsUsedUntilExpiry()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queueToken();
        $storage = new MemoryTokenStorage();
        $provider = new OAuthTokenProvider($this->config(), $http, $storage);

        $provider->getToken();
        $provider->getToken();
        $this->assertCount(1, $http->requests);

        $key = 'https://api-satusehat-dev.dto.kemkes.go.id/oauth2/v1|client';
        $storage->save($key, new AccessToken('old', time() + 30));
        $this->assertSame('token-2', $provider->getToken());
        $this->assertCount(2, $http->requests);
    }

    public function testInvalidClientThrowsAuthenticationException()
    {
        $http = new MockHttpClient();
        $http->queueFixture('invalid-client');
        $provider = new OAuthTokenProvider($this->config(), $http);

        $exception = $this->captureException('BangunCode\Ihs\Exception\AuthenticationException', function () use ($provider) {
            $provider->getToken();
        });
        $this->assertSame('invalid_client', $exception->getErrorCode());
    }

    public function testAccessTokenExpiry()
    {
        $token = new AccessToken('value', 1000);

        $this->assertFalse($token->isExpired(60, 900));
        $this->assertTrue($token->isExpired(60, 940));
        $this->assertNull(AccessToken::fromArray(array('access_token' => '')));
        $this->assertEquals($token, AccessToken::fromArray($token->toArray()));
    }

    public function testFileTokenStorageRoundTrip()
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ihs-test-' . uniqid();
        $storage = new FileTokenStorage($directory);

        $this->assertNull($storage->get('key'));
        $storage->save('key', new AccessToken('abc', 2000000000));
        $this->assertSame('abc', $storage->get('key')->getValue());

        $storage->save('key', new AccessToken('def', 2000000000));
        $again = new FileTokenStorage($directory);
        $this->assertSame('def', $again->get('key')->getValue());

        $storage->delete('key');
        $this->assertNull($storage->get('key'));
        rmdir($directory);
    }
}
