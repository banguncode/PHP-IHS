<?php

/**
 * Shared setup for the examples.
 *
 * Export SATUSEHAT_CLIENT_ID, SATUSEHAT_CLIENT_SECRET and SATUSEHAT_ORGANIZATION_ID, then run an
 * example with: php examples/02-outpatient-visit.php
 *
 * Patient, practitioner, location and encounter ids in the examples are the sample ids of the
 * SATUSEHAT development environment used by the Postman collections. Replace them with your own.
 */

use BangunCode\Ihs\Auth\FileTokenStorage;
use BangunCode\Ihs\Client;
use BangunCode\Ihs\Config;

require __DIR__ . '/../vendor/autoload.php';

$config = Config::fromEnvironment(array('environment' => 'development'));

return new Client($config, null, new FileTokenStorage(sys_get_temp_dir() . '/satusehat'));
