<?php

/**
 * An outpatient visit sent as one transaction Bundle.
 *
 * Entries refer to each other through the fullUrl returned by TransactionBundle::create().
 */

use BangunCode\Ihs\Client;
use BangunCode\Ihs\Fhir\TransactionBundle;

/** @var Client $client */
$client = require __DIR__ . '/bootstrap.php';

$patient = array('reference' => 'Patient/P02478375538', 'display' => 'Salsabilla Anjani Rizki');
$practitioner = array('reference' => 'Practitioner/N10000001', 'display' => 'Dokter Bronsig');

$bundle = new TransactionBundle();

$encounter = $bundle->create(array(
    'resourceType' => 'Encounter',
    'identifier' => array(array('system' => $client->identifierSystem('encounter'), 'value' => 'REG-0002')),
    'status' => 'finished',
    'class' => array(
        'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
        'code' => 'AMB',
        'display' => 'ambulatory',
    ),
    'subject' => $patient,
    'participant' => array(array('individual' => $practitioner)),
    'period' => array('start' => '2023-08-31T00:00:00+00:00', 'end' => '2023-08-31T02:00:00+00:00'),
    'statusHistory' => array(
        array('status' => 'arrived', 'period' => array('start' => '2023-08-31T00:00:00+00:00', 'end' => '2023-08-31T01:00:00+00:00')),
        array('status' => 'in-progress', 'period' => array('start' => '2023-08-31T01:00:00+00:00', 'end' => '2023-08-31T02:00:00+00:00')),
        array('status' => 'finished', 'period' => array('start' => '2023-08-31T02:00:00+00:00', 'end' => '2023-08-31T02:00:00+00:00')),
    ),
    'serviceProvider' => array('reference' => 'Organization/' . $client->getConfig()->getOrganizationId()),
));

$bundle->create(array(
    'resourceType' => 'Condition',
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
        'code' => 'K35.8',
        'display' => 'Acute appendicitis, other and unspecified',
    ))),
    'subject' => $patient,
    'encounter' => array('reference' => $encounter),
));

$result = $client->transaction($bundle);

foreach ($result->getCreatedIds() as $created) {
    printf("%s %s: %s\n", $created['resourceType'], $created['id'], $created['status']);
}
