<?php

/**
 * Patient and practitioner lookups.
 */

use BangunCode\Ihs\Client;

/** @var Client $client */
$client = require __DIR__ . '/bootstrap.php';

$patient = $client->patient()->findByNik('9271060312000001')->first();
$sameByDemographics = $client->patient()->findByNameBirthDateGender('patient 8', '2015-01-09', 'female');
$practitioner = $client->practitioner()->findByNik('7209061211900001')->first();

if ($patient === null || $practitioner === null) {
    exit("Patient or practitioner not found.\n");
}

printf("Patient %s: %s\n", $patient['id'], $patient['name'][0]['text']);
printf("Practitioner %s: %s\n", $practitioner['id'], $practitioner['name'][0]['text']);
printf("Demographic search returned %d match(es).\n", count($sameByDemographics));
