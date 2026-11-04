<?php

namespace BangunCode\Ihs\Auth;

/**
 * Persists access tokens between requests.
 *
 * A token is valid for a limited time and every failed token request is penalised with a one
 * minute wait, so long running deployments (PHP-FPM, queue workers) should share tokens through
 * a persistent storage instead of requesting a new one per process. Implement this interface to
 * use Redis, Memcached, APCu or a database.
 */
interface TokenStorageInterface
{
    /**
     * @param string $key
     *
     * @return AccessToken|null
     */
    public function get($key);

    /**
     * @param string      $key
     * @param AccessToken $token
     *
     * @return void
     */
    public function save($key, AccessToken $token);

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key);
}
