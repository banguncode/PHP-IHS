<?php

namespace BangunCode\Ihs\Auth;

/**
 * Supplies the bearer token sent with every FHIR request.
 */
interface TokenProviderInterface
{
    /**
     * @return string A valid access token.
     */
    public function getToken();

    /**
     * Discards the current token so the next getToken() call requests a new one.
     *
     * @return void
     */
    public function invalidate();
}
