<?php

namespace BangunCode\Ihs\Tests;

use BangunCode\Ihs\Fhir\JsonPatch;
use BangunCode\Ihs\Fhir\TransactionBundle;
use BangunCode\Ihs\Tests\Support\MockHttpClient;

final class ClientTest extends TestCase
{
    const BASE = 'https://api-satusehat-dev.dto.kemkes.go.id/fhir-r4/v1';

    public function testCreatePostsResourceWithBearerToken()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(201, array('resourceType' => 'Encounter', 'id' => 'enc-1'));

        $result = $this->client($http)->encounter()->create(array('status' => 'arrived'));

        $request = $http->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(self::BASE . '/Encounter', $request->getUrl());
        $this->assertSame('Bearer token-1', $request->getHeader('Authorization'));
        $this->assertSame('application/json', $request->getHeader('Content-Type'));
        $this->assertSame('{"resourceType":"Encounter","status":"arrived"}', $request->getBody());
        $this->assertSame('enc-1', $result['id']);
    }

    public function testCreateAcceptsJsonStringAndRejectsWrongResourceType()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(201, array('resourceType' => 'Condition', 'id' => 'c-1'));
        $client = $this->client($http);

        $client->condition()->create('{"resourceType":"Condition","clinicalStatus":{}}');
        $this->assertSame('{"resourceType":"Condition","clinicalStatus":{}}', $http->lastRequest()->getBody());

        $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () use ($client) {
            $client->condition()->create(array('resourceType' => 'Observation'));
        });
    }

    public function testTokenIsReusedAcrossRequests()
    {
        $http = new MockHttpClient();
        $http->queueToken()
            ->queue(200, array('resourceType' => 'Patient', 'id' => 'P1'))
            ->queue(200, array('resourceType' => 'Patient', 'id' => 'P2'));
        $client = $this->client($http);

        $client->patient()->read('P1');
        $client->patient()->read('P2');

        $this->assertCount(3, $http->requests);
        $this->assertSame(self::BASE . '/Patient/P2', $http->lastRequest()->getUrl());
    }

    public function testUpdateFillsIdAndRejectsMismatch()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(200, array('resourceType' => 'Encounter', 'id' => 'abc'));
        $client = $this->client($http);

        $client->encounter()->update('abc', array('status' => 'finished'));
        $request = $http->lastRequest();
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame(self::BASE . '/Encounter/abc', $request->getUrl());
        $this->assertSame('{"resourceType":"Encounter","status":"finished","id":"abc"}', $request->getBody());

        $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () use ($client) {
            $client->encounter()->update('abc', array('id' => 'abd'));
        });
    }

    public function testPatchUsesJsonPatchContentType()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(200, array('resourceType' => 'ClinicalImpression', 'id' => 'ci'));

        $this->client($http)->clinicalImpression()->patch(
            'ci',
            JsonPatch::create()->add('/problem', array(array('reference' => 'Condition/1')))
        );

        $request = $http->lastRequest();
        $this->assertSame('PATCH', $request->getMethod());
        $this->assertSame('application/json-patch+json', $request->getHeader('Content-Type'));
        $this->assertSame('[{"op":"add","path":"/problem","value":[{"reference":"Condition/1"}]}]', $request->getBody());
    }

    public function testBedStatusPatchFollowsCollection()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(200, array('resourceType' => 'Location', 'id' => 'bed'));

        $this->client($http)->location()->setBedStatus('bed', 'O', true);

        $operations = json_decode($http->lastRequest()->getBody(), true);
        $this->assertCount(3, $operations);
        $this->assertSame(array('op' => 'replace', 'path' => '/operationalStatus/display', 'value' => 'Occupied'), $operations[2]);
    }

    public function testSearchHelpersBuildCollectionQueries()
    {
        $http = new MockHttpClient();
        $http->queueToken();
        for ($i = 0; $i < 6; $i++) {
            $http->queue(200, array('resourceType' => 'Bundle', 'type' => 'searchset', 'total' => 0));
        }
        $client = $this->client($http);

        $client->patient()->findByNik('9271060312000001');
        $this->assertSame(
            self::BASE . '/Patient?identifier=https%3A%2F%2Ffhir.kemkes.go.id%2Fid%2Fnik%7C9271060312000001',
            $http->lastRequest()->getUrl()
        );

        $client->practitioner()->findByNameBirthDateGender('Practitioner 1', '2024-02-20', 'male');
        $this->assertSame(
            self::BASE . '/Practitioner?name=Practitioner%201&gender=male&birthdate=2024-02-20',
            $http->lastRequest()->getUrl()
        );

        $client->documentReference()->findByPrescriptionNumber('241203-DDG2BM2', true);
        $this->assertSame(
            self::BASE . '/DocumentReference?identifier=http%3A%2F%2Fsys-ids.kemkes.go.id%2Fprescription%2Fnational'
            . '%7C241203-DDG2BM2&_include=DocumentReference%3Arelated',
            $http->lastRequest()->getUrl()
        );

        $client->medicationDispense()->findByEncounter('enc', 'P1');
        $this->assertSame(self::BASE . '/MedicationDispense?context=enc&subject=P1', $http->lastRequest()->getUrl());

        $client->imagingStudy()->findByAccessionNumber('ACC1');
        $this->assertSame(
            self::BASE . '/ImagingStudy?identifier=http%3A%2F%2Fsys-ids.kemkes.go.id%2Facsn%2F10000004%7CACC1',
            $http->lastRequest()->getUrl()
        );

        $client->observation()->search(array('code' => array('a', 'b')));
        $this->assertSame(self::BASE . '/Observation?code=a&code=b', $http->lastRequest()->getUrl());
    }

    public function testSearchAllFollowsNextLinks()
    {
        $http = new MockHttpClient();
        $http->queueToken()
            ->queue(200, array(
                'resourceType' => 'Bundle',
                'link' => array(array('relation' => 'next', 'url' => self::BASE . '/Condition?page=2')),
                'entry' => array(array('resource' => array('resourceType' => 'Condition', 'id' => '1'))),
            ))
            ->queue(200, array(
                'resourceType' => 'Bundle',
                'entry' => array(array('resource' => array('resourceType' => 'Condition', 'id' => '2'))),
            ));

        $ids = array();
        foreach ($this->client($http)->condition()->searchAll(array('subject' => 'P1')) as $condition) {
            $ids[] = $condition['id'];
        }

        $this->assertSame(array('1', '2'), $ids);
        $this->assertSame(self::BASE . '/Condition?page=2', $http->lastRequest()->getUrl());
    }

    public function testTransactionPostsBundleToBaseUrl()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(200, array(
            'resourceType' => 'Bundle',
            'type' => 'transaction-response',
            'entry' => array(array('response' => array(
                'status' => '201 Created',
                'location' => self::BASE . '/Encounter/e1/_history/h1',
            ))),
        ));

        $bundle = new TransactionBundle();
        $bundle->create(array('resourceType' => 'Encounter'));
        $result = $this->client($http)->transaction($bundle);

        $this->assertSame(self::BASE, $http->lastRequest()->getUrl());
        $this->assertSame(
            array(array('resourceType' => 'Encounter', 'id' => 'e1', 'status' => '201 Created')),
            $result->getCreatedIds()
        );
    }

    public function testExpiredTokenIsRenewedOnce()
    {
        $http = new MockHttpClient();
        $http->queueToken()
            ->queueFixture('token-expired')
            ->queueToken()
            ->queue(200, array('resourceType' => 'Patient', 'id' => 'P1'));

        $patient = $this->client($http)->patient()->read('P1');

        $this->assertSame('P1', $patient['id']);
        $this->assertCount(4, $http->requests);
        $this->assertSame('Bearer token-2', $http->lastRequest()->getHeader('Authorization'));
    }

    public function testApiProductMismatchIsNotRetried()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queueFixture('no-api-product');
        $client = $this->client($http);

        $exception = $this->captureException('BangunCode\Ihs\Exception\AuthenticationException', function () use ($client) {
            $client->patient()->read('P1');
        });

        $this->assertFalse($exception->isTokenRejected());
        $this->assertCount(2, $http->requests);
    }

    public function testConsentOutcomeWithStatus200Throws()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queueFixture('consent-suppressed');
        $client = $this->client($http);

        $exception = $this->captureException('BangunCode\Ihs\Exception\ConsentException', function () use ($client) {
            $client->patient()->read('P1');
        });
        $this->assertSame(200, $exception->getStatusCode());
        $this->assertSame('suppressed', $exception->getErrorCode());
    }

    public function testTokenIsNotSentToForeignHosts()
    {
        $http = new MockHttpClient();
        $client = $this->client($http);

        $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () use ($client) {
            $client->request('GET', 'https://example.com/fhir/Patient');
        });
        $this->assertCount(0, $http->requests);
    }

    public function testDicomRouterUsesConfiguredHost()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(201, array('resourceType' => 'ServiceRequest', 'id' => 'sr'));

        $client = $this->client($http, array('dicom_router_url' => 'http://10.0.0.5:8080/fhir/'));
        $client->dicomRouter()->createServiceRequest(array('status' => 'active'));

        $this->assertSame('http://10.0.0.5:8080/fhir/ServiceRequest', $http->lastRequest()->getUrl());
        $this->assertSame('{"resourceType":"ServiceRequest","status":"active"}', $http->lastRequest()->getBody());
    }

    public function testEndpointAccessors()
    {
        $client = $this->client(new MockHttpClient());

        $this->assertInstanceOf('BangunCode\Ihs\Endpoint\PatientEndpoint', $client->patient());
        $this->assertSame('MedicationAdministration', $client->medicationAdministration()->getType());
        $this->assertSame('Immunization', $client->resource('Immunization')->getType());
        $this->assertSame($client->goal(), $client->resource('Goal'));
        $this->assertSame('http://sys-ids.kemkes.go.id/encounter/10000004', $client->identifierSystem('encounter'));

        $this->captureException('BadMethodCallException', function () use ($client) {
            $client->immunization();
        });
        $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () use ($client) {
            $client->resource('patient/../x');
        });
    }

    public function testLastResponseExposesHeaders()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(201, array('resourceType' => 'Goal', 'id' => 'g'), array('ETag' => 'W/"1"'));
        $client = $this->client($http);

        $client->goal()->create(array());

        $this->assertSame('W/"1"', $client->getLastResponse()->getHeader('etag'));
    }

    public function testPatientPatchEnvelope()
    {
        $http = new MockHttpClient();
        $http->queueToken()
            ->queue(200, array('code' => 200, 'error' => false, 'message' => 'Patient data updated successfully', 'data' => array('patient_id' => 'P1')))
            ->queue(200, array('code' => 400, 'error' => true, 'message' => 'Failed to update patient data'));
        $client = $this->client($http);

        $result = $client->patient()->patch('P1', array(array('op' => 'test', 'path' => '/gender', 'value' => 'female')));
        $this->assertSame('P1', $result['data']['patient_id']);

        $exception = $this->captureException('BangunCode\Ihs\Exception\ValidationException', function () use ($client) {
            $client->patient()->patch('P1', array(array('op' => 'replace', 'path' => '/gender', 'value' => 'female')));
        });
        $this->assertSame(200, $exception->getStatusCode());
    }
}
