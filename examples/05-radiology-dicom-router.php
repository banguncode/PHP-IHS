<?php

/**
 * Radiology order sent to SATUSEHAT and to the on premise DICOM Router, then the ImagingStudy
 * lookup by accession number. Requires the dicom_router_url option, for example through
 * SATUSEHAT_DICOM_ROUTER_URL=http://10.0.0.5:8080/fhir.
 */

use BangunCode\Ihs\Client;

/** @var Client $client */
$client = require __DIR__ . '/bootstrap.php';

$accessionNumber = 'ACSN-20230604-0001';

$serviceRequest = array(
    'resourceType' => 'ServiceRequest',
    'identifier' => array(
        array('system' => $client->identifierSystem('servicerequest'), 'value' => 'RAD-0001'),
        array(
            'use' => 'usual',
            'type' => array('coding' => array(array(
                'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                'code' => 'ACSN',
            ))),
            'system' => $client->identifierSystem('acsn'),
            'value' => $accessionNumber,
        ),
    ),
    'status' => 'active',
    'intent' => 'original-order',
    'category' => array(array('coding' => array(array(
        'system' => 'http://snomed.info/sct',
        'code' => '363679005',
        'display' => 'Imaging',
    )))),
    'code' => array('coding' => array(array(
        'system' => 'http://loinc.org',
        'code' => '24648-8',
        'display' => 'XR Chest PA upright',
    ))),
    'subject' => array('reference' => 'Patient/P02478375538'),
    'encounter' => array('reference' => 'Encounter/2823ed1d-3e3e-434e-9a5b-9c579d192787'),
    'requester' => array('reference' => 'Practitioner/N10000001'),
);

$created = $client->serviceRequest()->create($serviceRequest);
$serviceRequest['id'] = $created['id'];
$client->dicomRouter()->createServiceRequest($serviceRequest);

$study = $client->imagingStudy()->findByAccessionNumber($accessionNumber)->first();
echo $study === null
    ? "No ImagingStudy yet, the modality has not sent its images.\n"
    : "ImagingStudy {$study['id']} is available.\n";
