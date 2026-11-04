<?php

namespace BangunCode\Ihs\Auth;

use BangunCode\Ihs\Exception\InvalidArgumentException;

/**
 * Stores tokens as JSON files so that every PHP process on the host shares them.
 *
 * Files are written atomically with mode 0600. Keep the directory outside the web root.
 */
final class FileTokenStorage implements TokenStorageInterface
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @param string $directory Created with mode 0700 when missing.
     */
    public function __construct($directory)
    {
        $directory = rtrim((string) $directory, '/\\');
        if ($directory === '') {
            throw new InvalidArgumentException('Token storage directory must not be empty.');
        }
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('Token storage directory "%s" cannot be created.', $directory));
        }
        if (!is_writable($directory)) {
            throw new InvalidArgumentException(sprintf('Token storage directory "%s" is not writable.', $directory));
        }
        $this->directory = $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return null;
        }
        $contents = @file_get_contents($path);

        return $contents === false ? null : AccessToken::fromArray(json_decode($contents, true));
    }

    /**
     * {@inheritdoc}
     */
    public function save($key, AccessToken $token)
    {
        $path = $this->path($key);
        $temporary = $path . '.' . uniqid('', true) . '.tmp';

        if (@file_put_contents($temporary, json_encode($token->toArray()), LOCK_EX) === false) {
            return;
        }
        @chmod($temporary, 0600);
        if (!@rename($temporary, $path)) {
            @unlink($path);
            if (!@rename($temporary, $path)) {
                @unlink($temporary);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $path = $this->path($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function path($key)
    {
        return $this->directory . DIRECTORY_SEPARATOR . 'satusehat-token-' . sha1($key) . '.json';
    }
}
