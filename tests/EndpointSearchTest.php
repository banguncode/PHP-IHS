<?php

namespace BangunCode\Ihs\Tests;

use BangunCode\Ihs\Client;
use BangunCode\Ihs\Tests\Support\MockHttpClient;

/**
 * Every search helper must produce the request of the matching SATUSEHAT Postman example.
 */
final class EndpointSearchTest extends TestCase
{
    /**
     * @return array
     */
    public static function searches()
    {
        return array(
            'location by identifier' => array(function (Client $c) {
                $c->location()->findByIdentifier('G-2-R-1A');
            }, 'Location?identifier=http://sys-ids.kemkes.go.id/location/10000004|G-2-R-1A'),
            'location by name' => array(function (Client $c) {
                $c->location()->findByName('ruang');
            }, 'Location?name=ruang'),
            'organization by name' => array(function (Client $c) {
                $c->organization()->findByName('paramarta');
            }, 'Organization?name=paramarta'),
            'organization by partof' => array(function (Client $c) {
                $c->organization()->findByPartOf('10000004');
            }, 'Organization?partof=10000004'),
            'patient by mother nik' => array(function (Client $c) {
                $c->patient()->findByMotherNik('9104025209000006', '2024-12-09');
            }, 'Patient?identifier=https://fhir.kemkes.go.id/id/nik-ibu|9104025209000006&birthdate=2024-12-09'),
            'patient by name and nik' => array(function (Client $c) {
                $c->patient()->findByNameNik('Salsabilla Anjani Rizki', '9104025209000006');
            }, 'Patient?name=Salsabilla Anjani Rizki&identifier=https://fhir.kemkes.go.id/id/nik|9104025209000006'),
            'patient by name birthdate nik' => array(function (Client $c) {
                $c->patient()->findByNameBirthDateNik('Ghina Assyifa', '2004-08-21', '9104224606000005');
            }, 'Patient?name=Ghina Assyifa&birthdate=2004-08-21&identifier=https://fhir.kemkes.go.id/id/nik|9104224606000005'),
            'practitioner by name and nik' => array(function (Client $c) {
                $c->practitioner()->findByNameNik('dr. Alexander', '7209061211900001');
            }, 'Practitioner?name=dr. Alexander&identifier=https://fhir.kemkes.go.id/id/nik|7209061211900001'),
            'encounter by subject' => array(function (Client $c) {
                $c->encounter()->findBySubject('100000030009');
            }, 'Encounter?subject=100000030009'),
            'condition by subject and encounter' => array(function (Client $c) {
                $c->condition()->findBySubject('100000030009', 'enc-1');
            }, 'Condition?subject=100000030009&encounter=enc-1'),
            'procedure by encounter' => array(function (Client $c) {
                $c->procedure()->findByEncounter('enc-1');
            }, 'Procedure?encounter=enc-1'),
            'observation by based-on' => array(function (Client $c) {
                $c->observation()->findByBasedOn('sr-1', '100000030009');
            }, 'Observation?based-on=sr-1&subject=100000030009'),
            'diagnostic report by specimen' => array(function (Client $c) {
                $c->diagnosticReport()->findBySpecimen('sp-1', '100000030009');
            }, 'DiagnosticReport?specimen=sp-1&subject=100000030009'),
            'medication request by subject' => array(function (Client $c) {
                $c->medicationRequest()->findBySubject('100000030009');
            }, 'MedicationRequest?subject=100000030009'),
            'medication dispense by prescription' => array(function (Client $c) {
                $c->medicationDispense()->findByPrescription('100000030004', 'mr-1');
            }, 'MedicationDispense?subject=100000030004&prescription=mr-1'),
            'allergy by patient and code' => array(function (Client $c) {
                $c->allergyIntolerance()->findByPatient('100000030009', '89811004');
            }, 'AllergyIntolerance?patient=100000030009&code=89811004'),
            'allergy by patient' => array(function (Client $c) {
                $c->allergyIntolerance()->findByPatient('100000030009');
            }, 'AllergyIntolerance?patient=100000030009'),
            'immunization by patient and date' => array(function (Client $c) {
                $c->immunization()->findByPatient('100000030009', '2022-01-11');
            }, 'Immunization?date=2022-01-11&patient=100000030009'),
            'episode of care by care manager' => array(function (Client $c) {
                $c->episodeOfCare()->findByCareManager('100000030009', '10000004', 'N10000001');
            }, 'EpisodeOfCare?subject=100000030009&organization=10000004&care-manager=N10000001'),
            'questionnaire response by encounter' => array(function (Client $c) {
                $c->questionnaireResponse()->findByEncounter('enc-1', 'P02280547535');
            }, 'QuestionnaireResponse?encounter=enc-1&patient=P02280547535'),
            'service request by accession number' => array(function (Client $c) {
                $c->serviceRequest()->findByAccessionNumber('21120054');
            }, 'ServiceRequest?identifier=http://sys-ids.kemkes.go.id/acsn/10000004|21120054'),
            'service request by referral number' => array(function (Client $c) {
                $c->serviceRequest()->findByReferralNumber('1000000005');
            }, 'ServiceRequest?identifier=http://sys-ids.kemkes.go.id/referral-number|1000000005'),
            'specimen by request' => array(function (Client $c) {
                $c->specimen()->findByRequest('sr-1');
            }, 'Specimen?request=sr-1'),
            'specimen by collector' => array(function (Client $c) {
                $c->specimen()->findBySubjectAndCollector('100000030009', '1001194237');
            }, 'Specimen?subject=100000030009&collector=1001194237'),
            'specimen by collected date' => array(function (Client $c) {
                $c->specimen()->findBySubjectAndCollectedDate('100000030009', '2022-06-28');
            }, 'Specimen?subject=100000030009&collected=2022-06-28'),
            'specimen by accession number' => array(function (Client $c) {
                $c->specimen()->findByAccessionNumber('1000000004', 'sr-1');
            }, 'Specimen?identifier=http://sys-ids.kemkes.go.id/specimen-acsn|1000000004&request=sr-1'),
            'related person by nik' => array(function (Client $c) {
                $c->relatedPerson()->findByNik('367400001111222');
            }, 'RelatedPerson?identifier=https://fhir.kemkes.go.id/id/nik|367400001111222'),
            'coverage by patient' => array(function (Client $c) {
                $c->coverage()->findByPatient('P1', '100025');
            }, 'Coverage?patient=P1&identifier=https://sys-ids.kemkes.go.id/insurance-subscriber/100025|'),
            'eligibility request by identifier' => array(function (Client $c) {
                $c->coverageEligibilityRequest()->findByIdentifier('100025', 'SEP-1', 'P1');
            }, 'CoverageEligibilityRequest?identifier=https://sys-ids.kemkes.go.id/insurance-number/100025|SEP-1&patient=P1'),
            'eligibility response by request' => array(function (Client $c) {
                $c->coverageEligibilityResponse()->findByRequest('cer-1');
            }, 'CoverageEligibilityResponse?request=cer-1'),
            'charge item by account' => array(function (Client $c) {
                $c->chargeItem()->findByAccount('acc-1', 'P1');
            }, 'ChargeItem?account=acc-1&subject=P1'),
            'claim by sep number' => array(function (Client $c) {
                $c->claim()->findBySepNumber('100025', 'SEP-1');
            }, 'Claim?identifier=http://sys-ids.kemkes.go.id/claim-number/100025|SEP-1'),
            'payment reconciliation by claim' => array(function (Client $c) {
                $c->paymentReconciliation()->findByClaim('cl-1');
            }, 'PaymentReconciliation?detail-request=cl-1'),
            'payment notice by claim' => array(function (Client $c) {
                $c->paymentNotice()->findByClaim('cl-1');
            }, 'PaymentNotice?request=cl-1'),
        );
    }

    /**
     * @dataProvider searches
     *
     * @param callable $call
     * @param string   $expected Path and query relative to the FHIR base URL, URL decoded.
     */
    public function testSearchMatchesCollection($call, $expected)
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(200, array('resourceType' => 'Bundle', 'type' => 'searchset'));

        call_user_func($call, $this->client($http));

        $request = $http->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(
            'https://api-satusehat-dev.dto.kemkes.go.id/fhir-r4/v1/' . $expected,
            rawurldecode($request->getUrl())
        );
    }

    public function testEverythingOperation()
    {
        $http = new MockHttpClient();
        $http->queueToken()->queue(201, array(
            'resourceType' => 'Bundle',
            'entry' => array(array('resource' => array('resourceType' => 'CoverageEligibilityRequest', 'id' => 'cer-1'))),
        ));
        $client = $this->client($http);

        $bundle = $client->coverageEligibilityRequest()->everything('cer-1');

        $this->assertSame(
            'https://api-satusehat-dev.dto.kemkes.go.id/fhir-r4/v1/CoverageEligibilityRequest/cer-1/$everything',
            rawurldecode($http->lastRequest()->getUrl())
        );
        $this->assertSame('cer-1', $bundle->first()['id']);

        $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () use ($client) {
            $client->coverageEligibilityRequest()->operation('cer-1', 'everything');
        });
    }

}
