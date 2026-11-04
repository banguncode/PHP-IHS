<?php

/**
 * Handling SATUSEHAT error responses.
 */

use BangunCode\Ihs\Client;
use BangunCode\Ihs\Exception\ApiException;
use BangunCode\Ihs\Exception\AuthenticationException;
use BangunCode\Ihs\Exception\ConsentException;
use BangunCode\Ihs\Exception\NotFoundException;
use BangunCode\Ihs\Exception\ServerException;
use BangunCode\Ihs\Exception\TransportException;
use BangunCode\Ihs\Exception\ValidationException;

/** @var Client $client */
$client = require __DIR__ . '/bootstrap.php';

try {
    $client->condition()->create(array(
        'subject' => array('reference' => 'Patient/P02478375538'),
        'encounter' => array('reference' => 'Encounter/00000000-0000-0000-0000-000000000000'),
    ));
} catch (ValidationException $e) {
    foreach ($e->getOperationOutcome() ? $e->getOperationOutcome()->getErrors() : array() as $issue) {
        printf("%s at %s\n", $issue->getMessage(), implode(', ', $issue->getExpression()));
    }
} catch (ConsentException $e) {
    echo "The patient has not given consent: {$e->getMessage()}\n";
} catch (NotFoundException $e) {
    echo "Not found: {$e->getMessage()}\n";
} catch (AuthenticationException $e) {
    echo "Check client_id, client_secret and environment ({$e->getErrorCode()}).\n";
} catch (ServerException $e) {
    echo "SATUSEHAT is unavailable, retry later. Request id: {$e->getRequestId()}\n";
} catch (ApiException $e) {
    echo "HTTP {$e->getStatusCode()}: {$e->getMessage()}\n";
} catch (TransportException $e) {
    echo "Network failure: {$e->getMessage()}\n";
}
