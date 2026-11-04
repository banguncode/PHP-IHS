<?php

namespace BangunCode\Ihs\Auth;

/**
 * Bearer token issued by the SATUSEHAT OAuth2 endpoint.
 */
final class AccessToken
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var int Unix timestamp.
     */
    private $expiresAt;

    /**
     * @param string $value
     * @param int    $expiresAt Unix timestamp.
     */
    public function __construct($value, $expiresAt)
    {
        $this->value = (string) $value;
        $this->expiresAt = (int) $expiresAt;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @param int      $leeway Seconds before the real expiry at which the token is already treated as expired.
     * @param int|null $now    Unix timestamp, defaults to the current time.
     *
     * @return bool
     */
    public function isExpired($leeway = 60, $now = null)
    {
        $now = $now === null ? time() : (int) $now;

        return $now + (int) $leeway >= $this->expiresAt;
    }

    /**
     * @return array{access_token: string, expires_at: int}
     */
    public function toArray()
    {
        return array('access_token' => $this->value, 'expires_at' => $this->expiresAt);
    }

    /**
     * @param mixed $data Output of toArray().
     *
     * @return AccessToken|null
     */
    public static function fromArray($data)
    {
        if (!is_array($data) || !isset($data['access_token'], $data['expires_at']) || $data['access_token'] === '') {
            return null;
        }

        return new self($data['access_token'], $data['expires_at']);
    }
}
