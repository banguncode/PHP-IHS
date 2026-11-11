<?php

namespace BangunCode\Ihs\Tests;

use BangunCode\Ihs\Client;
use BangunCode\Ihs\Exception\ApiException;
use BangunCode\Ihs\Http\Response;
use BangunCode\Ihs\Tests\Support\MockHttpClient;

/**
 * Master data, KFA, KPTL, national medical record and DICOM Router download requests, matched against
 * the SATUSEHAT Postman collections.
 */
final class ServiceTest extends TestCase
{
    const HOST = 'https://api-satusehat-dev.dto.kemkes.go.id';

    /**
     * @return array
     */
    public static function getRequests()
    {
        return array(
            'provinces' => array(function (Client $c) {
                $c->masterData()->provinces(array('11', '12'));
            }, '/masterdata/v1/provinces?codes=11,12'),
            'cities' => array(function (Client $c) {
                $c->masterData()->cities(array('11', '12'));
            }, '/masterdata/v1/cities?province_codes=11,12'),
            'districts' => array(function (Client $c) {
                $c->masterData()->districts(array('1103', '1104'));
            }, '/masterdata/v1/districts?city_codes=1103,1104'),
            'sub districts' => array(function (Client $c) {
                $c->masterData()->subDistricts(array('110301', '110302'));
            }, '/masterdata/v1/sub-districts?district_codes=110301,110302'),
            'region page' => array(function (Client $c) {
                $c->masterData()->regionPage('cities');
            }, '/masterdata/v2/cities?current_page=1&next=&prev='),
            'region page with codes' => array(function (Client $c) {
                $c->masterData()->regionPage('provinces', 2, 'MTI=', 'MTE=', array('11', '12'));
            }, '/masterdata/v2/provinces?current_page=2&next=MTI=&prev=MTE=&codes=11,12'),
            'facilities' => array(function (Client $c) {
                $c->masterData()->facilities('138');
            }, '/masterdata/v1/mastersaranaindex/mastersarana?limit=10&page=1&jenis_sarana=138'),
            'kfa product' => array(function (Client $c) {
                $c->kfa()->product('93000108');
            }, '/kfa-v2/products?identifier=kfa&code=93000108'),
            'kfa products' => array(function (Client $c) {
                $c->kfa()->products('farmasi', 1, 100, array('keyword' => 'panadol'));
            }, '/kfa-v2/products/all?page=1&size=100&product_type=farmasi&keyword=panadol'),
            'kfa jkn prices' => array(function (Client $c) {
                $c->kfa()->jknPrices('92000372');
            }, '/kfa/farmalkes-price-jkn?page=1&limit=10&kfa_code=92000372'),
        );
    }

    /**
     * @dataProvider getRequests
     *
     * @param callable $call
     * @param string   $expected Path and query on the API gateway, URL decoded.
     */
    public function testGetRequestMatchesCollection($call, $expected)
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(200, array('status' => 200, 'error' => false, 'message' => 'success', 'data' => array()));

        call_user_func($call, $this->client($http));

        $this->assertSame('GET', $http->lastRequest()->getMethod());
        $this->assertSame(self::HOST . $expected, rawurldecode($http->lastRequest()->getUrl()));
    }

    /**
     * @return array
     */
    public static function postRequests()
    {
        return array(
            'kptl codes' => array(function (Client $c) {
                $c->kptl()->codes('12027');
            }, '/kptl-api-v1/code', array('method' => 'get_all_code', 'query_string' => '12027', 'offset' => 0, 'limit' => 5)),
            'kptl base codes' => array(function (Client $c) {
                $c->kptl()->baseCodes('bro');
            }, '/kptl-api-v1/base_code', array('method' => 'get_all_base_code', 'query_string' => 'bro', 'offset' => 0, 'limit' => 5)),
            'kptl combinations' => array(function (Client $c) {
                $c->kptl()->baseCodeCombinations('13601');
            }, '/kptl-api-v1/base_code_combination', array('method' => 'get_modifiers', 'query_string' => '13601', 'max_iteration' => 2)),
            'kptl modifiers' => array(function (Client $c) {
                $c->kptl()->modifiers();
            }, '/kptl-api-v1/modifier', array('method' => 'get_modifier', 'query_string' => '', 'offset' => 0, 'limit' => 50)),
            'kptl modifier values' => array(function (Client $c) {
                $c->kptl()->modifierValues('M006');
            }, '/kptl-api-v1/modifier_value', array('method' => 'get_modifier_value', 'query_string' => 'M006')),
            'kptl base codes by modifier' => array(function (Client $c) {
                $c->kptl()->baseCodesByModifier('M110');
            }, '/kptl-api-v1/base_code_by_modifier', array('method' => 'get_base_code', 'query_string' => 'M110', 'offset' => 0, 'limit' => 10)),
            'kfa device templates' => array(function (Client $c) {
                $c->kfa()->deviceTemplates(array('search' => 'Surgical Gown'));
            }, '/kfa-v3/alkes/template', array('page' => 1, 'size' => 10, 'search' => 'Surgical Gown')),
            'kfa device products' => array(function (Client $c) {
                $c->kfa()->deviceProducts();
            }, '/kfa-v3/alkes/products', array('page' => 1, 'size' => 10)),
            'consent link' => array(function (Client $c) {
                $c->nationalMedicalRecord()->createConsentLink('P20396164684', 'BERRI SUANDI', '10008864122', 'Imam Fahrizal', 'Puskesmas Cibuntu', '100010939');
            }, '/ssrme/v2/ntl/chl', array(
                'patient_id' => 'P20396164684',
                'patient_name' => 'BERRI SUANDI',
                'practitioner_id' => '10008864122',
                'practitioner_name' => 'Imam Fahrizal',
                'organization_id' => '100010939',
                'organization_name' => 'Puskesmas Cibuntu',
            )),
            'open record' => array(function (Client $c) {
                $c->nationalMedicalRecord()->open('P1', 'Patient', 'N1', 'Practitioner', 'Clinic');
            }, '/ssrme/v2/ntl/shl', array(
                'patient_id' => 'P1',
                'patient_name' => 'Patient',
                'practitioner_id' => 'N1',
                'practitioner_name' => 'Practitioner',
                'organization_id' => '10000004',
                'organization_name' => 'Clinic',
            )),
        );
    }

    /**
     * @dataProvider postRequests
     *
     * @param callable $call
     * @param string   $path
     * @param array    $body
     */
    public function testPostRequestMatchesCollection($call, $path, array $body)
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(200, array('success' => true, 'code' => 200, 'data' => array()));

        call_user_func($call, $this->client($http));

        $request = $http->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(self::HOST . $path, $request->getUrl());
        $this->assertSame($body, json_decode($request->getBody(), true));
        $this->assertSame('Bearer token-1', $request->getHeader('Authorization'));
        $this->assertSame(strpos($path, '/kptl-api-v1/') === 0 ? 'true' : null, $request->getHeader('X-Encryption-Disabled'));
    }

    public function testGatewayUrlsFollowConfiguration()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(200, array())->queue(200, array());
        $client = $this->client($http, array('environment' => 'production', 'kptl_url' => 'https://kptl.example.test/v1/'));

        $client->kfa()->product('93000108', 'nie');
        $this->assertSame('https://api-satusehat.kemkes.go.id/kfa-v2/products?identifier=nie&code=93000108', $http->lastRequest()->getUrl());

        $client->kptl()->modifierValues('M006');
        $this->assertSame('https://kptl.example.test/v1/modifier_value', $http->lastRequest()->getUrl());

        $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () use ($client) {
            $client->masterData()->regionPage('villages');
        });
        $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () use ($client) {
            $client->kfa()->products('vaccine');
        });
    }

    /**
     * @return array
     */
    public static function errorResponses()
    {
        $ns = 'BangunCode\Ihs\Exception\\';

        return array(
            'consent required' => array(403, array('success' => false, 'code' => 403, 'message' => 'consent required', 'data' => array('code' => 'CONSENT_REQUIRED')), $ns . 'ConsentException', 'CONSENT_REQUIRED', 'consent required'),
            'wrong organization' => array(400, array('success' => false, 'code' => 400, 'data' => 'wrong organization'), $ns . 'ValidationException', null, 'wrong organization'),
            'patient not found' => array(400, array('success' => false, 'code' => 400, 'message' => 'patient with ID P1 not found'), $ns . 'ValidationException', null, 'patient with ID P1 not found'),
            'consent link failed' => array(500, array('status' => 500, 'error' => true, 'message' => 'ChaRME failed to create consent health link with code 200'), $ns . 'ServerException', null, 'ChaRME failed'),
            'kfa validation' => array(422, array('detail' => array(array('loc' => array('query', 'code'), 'msg' => 'field required', 'type' => 'value_error.missing'))), $ns . 'ValidationException', null, 'query.code: field required'),
        );
    }

    /**
     * @dataProvider errorResponses
     *
     * @param int         $status
     * @param array       $body
     * @param string      $class
     * @param string|null $errorCode
     * @param string      $messagePart
     */
    public function testErrorResponsesAreMapped($status, array $body, $class, $errorCode, $messagePart)
    {
        $exception = ApiException::fromResponse(new Response($status, array(), json_encode($body)));

        $this->assertInstanceOf($class, $exception);
        $this->assertSame($errorCode, $exception->getErrorCode());
        $this->assertTrue(strpos($exception->getMessage(), $messagePart) !== false, $exception->getMessage());
    }

    public function testFailedEnvelopeWithStatus200Throws()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(200, array('success' => false, 'code' => 403, 'message' => 'consent required', 'data' => array('code' => 'CONSENT_REQUIRED')));
        $client = $this->client($http);

        $this->captureException('BangunCode\Ihs\Exception\ConsentException', function () use ($client) {
            $client->nationalMedicalRecord()->open('P1', 'Patient', 'N1', 'Practitioner', 'Clinic');
        });
    }

    public function testDicomRouterDownload()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(200, "PK\x03\x04zip", array('Content-Type' => 'application/zip'));
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dicom-router-' . uniqid() . '.zip';

        $written = $this->client($http)->downloadDicomRouter($file, 'installer');

        $this->assertSame(self::HOST . '/dicom-router-installer', $http->lastRequest()->getUrl());
        $this->assertSame('*/*', $http->lastRequest()->getHeader('Accept'));
        $this->assertSame(7, $written);
        $this->assertSame("PK\x03\x04zip", file_get_contents($file));
        unlink($file);

        $client = $this->client(new MockHttpClient());
        $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () use ($client, $file) {
            $client->downloadDicomRouter($file, 'portable');
        });
    }
}
