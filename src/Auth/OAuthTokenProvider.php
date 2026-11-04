<?php

namespace BangunCode\Ihs\Auth;

use BangunCode\Ihs\Config;
use BangunCode\Ihs\Exception\ApiException;
use BangunCode\Ihs\Exception\InvalidArgumentException;
use BangunCode\Ihs\Exception\TransportException;
use BangunCode\Ihs\Http\HttpClientInterface;
use BangunCode\Ihs\Http\Request;

/**
 * Requests tokens with the OAuth2 client credentials grant and caches them until shortly before
 * they expire.
 */
final class OAuthTokenProvider implements TokenProviderInterface
{
    /**
     * Used when the token response carries no expires_in. Tokens are documented as valid for 60 minutes.
     */
    const DEFAULT_LIFETIME = 3600;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var HttpClientInterface
     */
    private $http;

    /**
     * @var TokenStorageInterface
     */
    private $storage;

    /**
     * @var int
     */
    private $leeway;

    /**
     * @param Config                     $config
     * @param HttpClientInterface        $http
     * @param TokenStorageInterface|null $storage Defaults to MemoryTokenStorage.
     * @param int                        $leeway  Seconds before expiry at which a token is renewed.
     */
    public function __construct(Config $config, HttpClientInterface $http, $storage = null, $leeway = 60)
    {
        if ($storage !== null && !$storage instanceof TokenStorageInterface) {
            throw new InvalidArgumentException('Token storage must implement TokenStorageInterface.');
        }
        $this->config = $config;
        $this->http = $http;
        $this->storage = $storage !== null ? $storage : new MemoryTokenStorage();
        $this->leeway = (int) $leeway;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ApiException       When SATUSEHAT rejects the credentials.
     * @throws TransportException When the OAuth2 endpoint cannot be reached.
     */
    public function getToken()
    {
        $key = $this->storageKey();
        $token = $this->storage->get($key);

        if ($token === null || $token->isExpired($this->leeway)) {
            $token = $this->requestToken();
            $this->storage->save($key, $token);
        }

        return $token->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function invalidate()
    {
        $this->storage->delete($this->storageKey());
    }

    /**
     * @return AccessToken
     */
    private function requestToken()
    {
        $request = new Request(
            'POST',
            $this->config->getAuthUrl() . '/accesstoken?grant_type=client_credentials',
            array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ),
            http_build_query(array(
                'client_id' => $this->config->getClientId(),
                'client_secret' => $this->config->getClientSecret(),
            ), '', '&')
        );

        $response = $this->http->send($request);
        $data = $response->getJson();

        if (!$response->isSuccessful() || !is_array($data) || empty($data['access_token'])) {
            throw ApiException::fromResponse($response);
        }

        $lifetime = isset($data['expires_in']) && is_numeric($data['expires_in'])
            ? (int) $data['expires_in']
            : self::DEFAULT_LIFETIME;

        return new AccessToken($data['access_token'], time() + $lifetime);
    }

    /**
     * @return string
     */
    private function storageKey()
    {
        return $this->config->getAuthUrl() . '|' . $this->config->getClientId();
    }
}
