<?php

namespace BangunCode\Ihs\Tests;

use BangunCode\Ihs\Exception\ApiException;
use BangunCode\Ihs\Http\Response;

/**
 * Error responses recorded in the SATUSEHAT Postman examples.
 */
final class ErrorMappingTest extends TestCase
{
    /**
     * @return array
     */
    public static function fixtures()
    {
        $ns = 'BangunCode\Ihs\Exception\\';

        return array(
            'consent suppressed' => array('consent-suppressed', $ns . 'ConsentException', 'suppressed', 'consent or privacy rules'),
            'not opted in' => array('not-opted-in', $ns . 'ConsentException', 'forbidden', 'business rules'),
            'id mismatch' => array('resource-id-mismatch', $ns . 'ValidationException', 'structure', 'resource id mismatch'),
            'unparseable' => array('unparseable-resource', $ns . 'ValidationException', 'structure', 'missing required field'),
            'reference not found' => array('reference-not-found', $ns . 'ValidationException', 'value', 'reference_not_found'),
            'patient mismatch' => array('patient-data-mismatch', $ns . 'ValidationException', 'exception', 'alamat TIDAK SESUAI'),
            'patient duplicate' => array('patient-duplicate', $ns . 'ValidationException', 'exception', 'Patient Resource Exists'),
            'newborn duplicate' => array('newborn-duplicate', $ns . 'ValidationException', 'duplicate', 'Found duplicate'),
            'mother not found' => array('mother-not-found', $ns . 'NotFoundException', 'exception', 'nik-ibu not found'),
            'no api product' => array('no-api-product', $ns . 'AuthenticationException', 'keymanagement.service.InvalidAPICallAsNoApiProductMatchFound', 'no apiproduct match'),
            'invalid client' => array('invalid-client', $ns . 'AuthenticationException', 'invalid_client', 'ClientId is Invalid'),
            'invalid token' => array('invalid-token', $ns . 'AuthenticationException', 'oauth.v2.InvalidAccessToken', 'Invalid access token'),
            'token expired' => array('token-expired', $ns . 'AuthenticationException', 'keymanagement.service.access_token_expired', 'Access Token expired'),
            'forbidden html' => array('forbidden-html', $ns . 'ForbiddenException', null, '403 Forbidden'),
            'wrong url' => array('wrong-url', $ns . 'NotFoundException', null, 'HTTP 404'),
            'no healthy upstream' => array('no-healthy-upstream', $ns . 'ServerException', null, 'no healthy upstream'),
            'citizen not found' => array('citizen-not-found', $ns . 'ValidationException', 'exception', 'Failed to get citizen'),
            'patient patch validation' => array('patient-patch-validation', $ns . 'ValidationException', null, "Failed to update patient data: Test operation for 'name' can not be empty"),
        );
    }

    /**
     * @dataProvider fixtures
     *
     * @param string      $fixture
     * @param string      $class
     * @param string|null $errorCode
     * @param string      $messagePart
     */
    public function testFixtureIsMapped($fixture, $class, $errorCode, $messagePart)
    {
        $data = json_decode(file_get_contents(__DIR__ . '/Fixtures/errors/' . $fixture . '.json'), true);
        $response = new Response($data['status'], array('Content-Type' => $data['content_type'], 'x-request-id' => 'req-1'), $data['body']);

        $exception = ApiException::fromResponse($response);

        $this->assertInstanceOf($class, $exception);
        $this->assertSame($errorCode, $exception->getErrorCode());
        $this->assertTrue(
            strpos($exception->getMessage(), $messagePart) !== false,
            sprintf('"%s" should contain "%s".', $exception->getMessage(), $messagePart)
        );
        $this->assertSame($data['status'], $exception->getStatusCode());
        $this->assertSame('req-1', $exception->getRequestId());
    }

    public function testOperationOutcomeDetails()
    {
        $data = json_decode(file_get_contents(__DIR__ . '/Fixtures/errors/patient-data-mismatch.json'), true);
        $exception = ApiException::fromResponse(new Response($data['status'], array(), $data['body']));
        $outcome = $exception->getOperationOutcome();

        $this->assertNotNull($outcome);
        $this->assertCount(2, $outcome->getErrors());
        $this->assertSame(array('Patient.gender'), $outcome->getIssues()[1]->getExpression());
        $this->assertFalse($outcome->isDuplicate());
    }

    public function testDuplicateDetection()
    {
        foreach (array('patient-duplicate', 'newborn-duplicate') as $fixture) {
            $data = json_decode(file_get_contents(__DIR__ . '/Fixtures/errors/' . $fixture . '.json'), true);
            $exception = ApiException::fromResponse(new Response($data['status'], array(), $data['body']));
            $this->assertTrue($exception->getOperationOutcome()->isDuplicate(), $fixture);
        }
    }
}
