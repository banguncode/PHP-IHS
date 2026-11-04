<?php

/**
 * E-prescription lookup by an outside pharmacy and its dispensing records.
 */

use BangunCode\Ihs\Client;

/** @var Client $client */
$client = require __DIR__ . '/bootstrap.php';

$prescriptionNumber = '241203-DDG2BM2';

$prescription = $client->documentReference()->findByPrescriptionNumber($prescriptionNumber, true);
$document = $prescription->first('DocumentReference');
if ($document === null) {
    exit("Prescription $prescriptionNumber not found.\n");
}

foreach ($prescription->getResources('MedicationRequest') as $request) {
    printf(
        "MedicationRequest %s: %s\n",
        $request['id'],
        isset($request['medicationReference']['display']) ? $request['medicationReference']['display'] : 'contained medication'
    );
}

$requests = $client->medicationRequest()->findByPrescriptionNumber($prescriptionNumber);
printf("%d MedicationRequest(s) carry national prescription number %s.\n", count($requests), $prescriptionNumber);

$patientId = 'P01654557057';
$pharmacyEncounterId = '58785160-5720-41d8-a05a-efafdeb856d3';
$dispensed = $client->medicationDispense()->findByEncounter($pharmacyEncounterId, $patientId);
printf("%d MedicationDispense(s) recorded in the pharmacy encounter.\n", count($dispensed));
