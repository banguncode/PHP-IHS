<?php

namespace BangunCode\Ihs\Exception;

/**
 * Thrown on HTTP 401: invalid client credentials, an invalid or expired access token, or an
 * API product mismatch caused by a wrong environment or a missing API role.
 */
class AuthenticationException extends ApiException
{
    /**
     * Whether the access token itself was rejected, in which case requesting a new token helps.
     *
     * @return bool
     */
    public function isTokenRejected()
    {
        return in_array($this->getErrorCode(), array(
            'keymanagement.service.access_token_expired',
            'oauth.v2.InvalidAccessToken',
        ), true);
    }
}
