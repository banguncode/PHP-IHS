<?php

namespace BangunCode\Ihs\Tests;

use BangunCode\Ihs\Client;
use BangunCode\Ihs\Config;
use BangunCode\Ihs\Tests\Support\MockHttpClient;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $options
     *
     * @return Config
     */
    protected function config(array $options = array())
    {
        return new Config(array_merge(array(
            'client_id' => 'client',
            'client_secret' => 'secret',
            'organization_id' => '10000004',
        ), $options));
    }

    /**
     * @param MockHttpClient $http
     * @param array          $options
     *
     * @return Client
     */
    protected function client(MockHttpClient $http, array $options = array())
    {
        return new Client($this->config($options), $http);
    }

    /**
     * Runs $callback and returns the exception it throws, failing when none or another type is thrown.
     *
     * @param string   $class
     * @param callable $callback
     *
     * @return \Exception
     */
    protected function captureException($class, $callback)
    {
        try {
            call_user_func($callback);
        } catch (\Exception $exception) {
            $this->assertInstanceOf($class, $exception);

            return $exception;
        }
        $this->fail('Expected ' . $class . ' to be thrown.');

        return null;
    }
}
