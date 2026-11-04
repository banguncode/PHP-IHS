<?php

/**
 * Outpatient visit sent resource by resource.
 */

use BangunCode\Ihs\Client;
use BangunCode\Ihs\Fhir\JsonPatch;

/** @var Client $client */
$client = require __DIR__ . '/bootstrap.php';

$organizationId = $client->getConfig()->getOrganizationId();
$patient = array('reference' => 'Patient/P02478375538', 'display' => 'Salsabilla Anjani Rizki');
$practitioner = array('reference' => 'Practitioner/N10000001', 'display' => 'Dokter Bronsig');
$location = array('reference' => 'Location/b017aa54-f1df-4ec2-9d84-8823815d7228', 'display' => 'Room 1A, Outpatient Clinic');
$arrived = '2023-06-04T05:24:47+00:00';

$encounter = $client->encounter()->create(array(
    'identifier' => array(array('system' => $client->identifierSystem('encounter'), 'value' => 'REG-0001')),
    'status' => 'arrived',
    'class' => array(
        'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
        'code' => 'AMB',
        'display' => 'ambulatory',
    ),
    'subject' => $patient,
    'participant' => array(array(
        'type' => array(array('coding' => array(array(
            'system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
            'code' => 'ATND',
            'display' => 'attender',
        )))),
        'individual' => $practitioner,
    )),
    'period' => array('start' => $arrived),
    'location' => array(array('location' => $location, 'period' => array('start' => $arrived))),
    'statusHistory' => array(array('status' => 'arrived', 'period' => array('start' => $arrived))),
    'serviceProvider' => array('reference' => 'Organization/' . $organizationId),
));
$encounterReference = array('reference' => 'Encounter/' . $encounter['id']);

$encounter['status'] = 'in-progress';
$encounter['statusHistory'][0]['period']['end'] = '2023-06-04T05:30:00+00:00';
$encounter['statusHistory'][] = array('status' => 'in-progress', 'period' => array('start' => '2023-06-04T05:30:00+00:00'));
$encounter = $client->encounter()->update($encounter['id'], $encounter);

$client->observation()->create(array(
    'status' => 'final',
    'category' => array(array('coding' => array(array(
        'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
        'code' => 'vital-signs',
        'display' => 'Vital Signs',
    )))),
    'code' => array('coding' => array(array(
        'system' => 'http://loinc.org',
        'code' => '8480-6',
        'display' => 'Systolic blood pressure',
    ))),
    'subject' => $patient,
    'encounter' => $encounterReference,
    'effectiveDateTime' => '2023-06-04T05:55:00+00:00',
    'issued' => '2023-06-04T05:55:00+00:00',
    'performer' => array($practitioner),
    'valueQuantity' => array(
        'value' => 133,
        'unit' => 'mm[Hg]',
        'system' => 'http://unitsofmeasure.org',
        'code' => 'mm[Hg]',
    ),
));

$diagnosis = $client->condition()->create(array(
    'clinicalStatus' => array('coding' => array(array(
        'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
        'code' => 'active',
        'display' => 'Active',
    ))),
    'category' => array(array('coding' => array(array(
        'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
        'code' => 'encounter-diagnosis',
        'display' => 'Encounter Diagnosis',
    )))),
    'code' => array('coding' => array(array(
        'system' => 'http://hl7.org/fhir/sid/icd-10',
        'code' => 'A91',
        'display' => 'Dengue haemorrhagic fever',
    ))),
    'subject' => $patient,
    'encounter' => $encounterReference,
    'recordedDate' => '2023-06-04T08:30:00+00:00',
));

$clinicalImpressionId = '21c76df9-af1f-4ed0-86f9-6baf28082bb6';
$client->clinicalImpression()->patch(
    $clinicalImpressionId,
    JsonPatch::create()->add('/problem', array(array('reference' => 'Condition/' . $diagnosis['id'])))
);

printf("Encounter %s is %s, diagnosis %s recorded.\n", $encounter['id'], $encounter['status'], $diagnosis['id']);
