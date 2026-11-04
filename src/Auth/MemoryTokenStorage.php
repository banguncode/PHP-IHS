<?php

namespace BangunCode\Ihs\Auth;

/**
 * Keeps tokens for the lifetime of the PHP process.
 */
final class MemoryTokenStorage implements TokenStorageInterface
{
    /**
     * @var array<string, AccessToken>
     */
    private $tokens = array();

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return isset($this->tokens[$key]) ? $this->tokens[$key] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function save($key, AccessToken $token)
    {
        $this->tokens[$key] = $token;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        unset($this->tokens[$key]);
    }
}
