# php-ihs

PHP client for the SATUSEHAT (Indonesia Health Services) FHIR R4 API of the Indonesian Ministry of Health.

## Requirements

PHP 5.5 or newer with the `curl` and `json` extensions.

## Installation

```bash
composer require banguncode/php-ihs
```

## Configuration

```php
use BangunCode\Ihs\Auth\FileTokenStorage;
use BangunCode\Ihs\Client;
use BangunCode\Ihs\Config;
use BangunCode\Ihs\Environment;

$client = new Client(
    new Config(array(
        'environment' => Environment::DEVELOPMENT,
        'client_id' => 'CLIENT_ID',
        'client_secret' => 'CLIENT_SECRET',
        'organization_id' => '10000004',
    )),
    null,
    new FileTokenStorage('/var/cache/satusehat')
);
```

| Option | Description |
| --- | --- |
| `client_id`, `client_secret` | Required. Credentials from the SATUSEHAT portal. |
| `environment` | `development` (default), `staging` or `production`. |
| `organization_id` | Organization id of the facility, used to build identifier systems. |
| `auth_url`, `base_url` | Override the URLs derived from `environment`. |
| `dicom_router_url` | FHIR base URL of the on premise DICOM Router. |
| `http` | `CurlHttpClient` options: `timeout`, `connect_timeout`, `verify`, `proxy`, `curl`. |

`Config::fromEnvironment()` reads the same options from `SATUSEHAT_ENV`, `SATUSEHAT_CLIENT_ID`, `SATUSEHAT_CLIENT_SECRET`, `SATUSEHAT_ORGANIZATION_ID`, `SATUSEHAT_AUTH_URL`, `SATUSEHAT_BASE_URL` and `SATUSEHAT_DICOM_ROUTER_URL`.

SATUSEHAT penalises every failed token request with a one minute wait, so share tokens between processes with `FileTokenStorage` or your own `TokenStorageInterface`. An expired token is renewed and the request is retried once.

## Usage

Each resource type has an accessor named after it, such as `encounter()`, `observation()` or `medicationRequest()`. Payloads may be arrays, JSON strings copied from the SATUSEHAT Postman collections, or `JsonSerializable` objects. Results are associative arrays.

```php
use BangunCode\Ihs\Fhir\JsonPatch;

$patient = $client->patient()->findByNik('9271060312000001')->first();

$encounter = $client->encounter()->create(array(
    'identifier' => array(array(
        'system' => $client->identifierSystem('encounter'),
        'value' => 'REG-0001',
    )),
    'status' => 'arrived',
    'subject' => array('reference' => 'Patient/' . $patient['id']),
));

$encounter['status'] = 'in-progress';
$client->encounter()->update($encounter['id'], $encounter);

$client->clinicalImpression()->patch($impressionId, JsonPatch::create()
    ->add('/problem', array(array('reference' => 'Condition/' . $conditionId))));

foreach ($client->condition()->searchAll(array('subject' => $patient['id'])) as $condition) {
    echo $condition['id'];
}
```

Every endpoint provides `create`, `read`, `update`, `patch`, `search`, `searchAll`, `searchByIdentifier` and `operation`. Clinical resources add `findBySubject($patientId, $encounterId)` and `findByEncounter($encounterId)`. Other lookups from the SATUSEHAT Postman collections have dedicated helpers:

| Resource | Helpers |
| --- | --- |
| Patient | `findByNik`, `findByMotherNik`, `findByNameNik`, `findByNameBirthDateNik`, `findByNameBirthDateGender` |
| Practitioner | `findByNik`, `findByNameNik`, `findByNameBirthDateGender` |
| Organization | `findByName`, `findByPartOf` |
| Location | `findByIdentifier`, `findByName`, `findByOrganization`, `setBedStatus` |
| Observation | `findByBasedOn` |
| DiagnosticReport | `findBySpecimen` |
| ServiceRequest | `findByAccessionNumber`, `findByReferralNumber` |
| Specimen | `findByRequest`, `findBySubjectAndCollector`, `findBySubjectAndCollectedDate`, `findByAccessionNumber` |
| MedicationRequest | `findByPrescriptionNumber` |
| MedicationDispense | `findByEncounter`, `findByPrescription` |
| DocumentReference | `findByPrescriptionNumber` |
| ImagingStudy | `findByAccessionNumber` |
| AllergyIntolerance, Immunization | `findByPatient` |
| QuestionnaireResponse | `findByEncounter` |
| EpisodeOfCare | `findByCareManager` |
| RelatedPerson | `findByNik` |
| Coverage | `findByPatient` |
| CoverageEligibilityRequest | `findByIdentifier`, `everything` |
| CoverageEligibilityResponse | `findByRequest` |
| ChargeItem | `findByAccount` |
| Claim | `findBySepNumber` |
| PaymentReconciliation, PaymentNotice | `findByClaim` |

Use `$client->resource('Basic')` for resource types without an accessor. See [examples](examples) for complete flows.

### Transaction Bundle

```php
use BangunCode\Ihs\Fhir\TransactionBundle;

$bundle = new TransactionBundle();
$encounter = $bundle->create($encounterResource);
$bundle->create(array(
    'resourceType' => 'Condition',
    'encounter' => array('reference' => $encounter),
));

foreach ($client->transaction($bundle)->getCreatedIds() as $created) {
    echo $created['resourceType'] . '/' . $created['id'];
}
```

`create()` returns the entry `fullUrl` (`urn:uuid:...`) for references between entries.

### DICOM Router

```php
$client->serviceRequest()->create($serviceRequest);
$client->dicomRouter()->createServiceRequest($serviceRequest);
$study = $client->imagingStudy()->findByAccessionNumber($accessionNumber)->first();
```

## Error Handling

All exceptions implement `BangunCode\Ihs\Exception\IhsException`.

| Exception | Raised when |
| --- | --- |
| `ValidationException` | HTTP 400 or 422: invalid JSON, profile rule violation, missing reference, id mismatch, duplicate resource. |
| `ConsentException` | The patient has not given consent, reported as HTTP 400 `forbidden` or HTTP 200 `suppressed`. |
| `AuthenticationException` | HTTP 401: invalid client, invalid or expired token, wrong environment or missing API role. |
| `ForbiddenException` | HTTP 403 from the gateway. |
| `NotFoundException` | HTTP 404: wrong URL or a referenced record that does not exist. |
| `ServerException` | HTTP 5xx. |
| `ApiException` | Parent of the exceptions above, used for any other status. |
| `TransportException` | No response: DNS, TLS or timeout failure. |
| `InvalidArgumentException` | Input rejected before the request is sent. |

```php
try {
    $client->patient()->create($patient);
} catch (ValidationException $e) {
    $outcome = $e->getOperationOutcome();
    if ($outcome !== null && $outcome->isDuplicate()) {
        $patient = $client->patient()->findByNik($nik)->first();
    }
} catch (ApiException $e) {
    error_log(sprintf('%s (%s), request id %s', $e->getMessage(), $e->getErrorCode(), $e->getRequestId()));
}
```

## Development

```bash
composer install
composer test
composer style
```

## License

MIT. See [LICENSE](LICENSE).
