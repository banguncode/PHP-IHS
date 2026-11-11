<?php

namespace BangunCode\Ihs\Service;

use BangunCode\Ihs\Exception\ConsentException;
use BangunCode\Ihs\Exception\InvalidArgumentException;

/**
 * National medical record (RME Nasional): a patient's clinical history across facilities, opened
 * only with the consent the patient gives in the SATUSEHAT Mobile app.
 *
 * The flow is createConsentLink(), the patient approves the verificationUrl, then open() returns
 * the shlinkUrl that shows the record. Responses use {"success", "code", "message", "data", "request_id"}.
 */
class NationalMedicalRecord extends ApiService
{
    /**
     * POST /ssrme/v2/ntl/chl
     *
     * @param string      $patientId
     * @param string      $patientName
     * @param string      $practitionerId
     * @param string      $practitionerName
     * @param string      $organizationName
     * @param string|null $organizationId   Defaults to the organization_id configuration option.
     *
     * @return array data holds shlinkId, verificationUrl and expiredAt.
     */
    public function createConsentLink($patientId, $patientName, $practitionerId, $practitionerName, $organizationName, $organizationId = null)
    {
        return $this->post('/ssrme/v2/ntl/chl', $this->context(
            $patientId,
            $patientName,
            $practitionerId,
            $practitionerName,
            $organizationName,
            $organizationId
        ));
    }

    /**
     * POST /ssrme/v2/ntl/shl
     *
     * @param string      $patientId
     * @param string      $patientName
     * @param string      $practitionerId
     * @param string      $practitionerName
     * @param string      $organizationName
     * @param string|null $organizationId   Defaults to the organization_id configuration option.
     *
     * @return array data holds consentId, expiredAt, partial, shlinkUrl and warnings.
     *
     * @throws ConsentException When the patient has not approved access yet (code CONSENT_REQUIRED).
     */
    public function open($patientId, $patientName, $practitionerId, $practitionerName, $organizationName, $organizationId = null)
    {
        return $this->post('/ssrme/v2/ntl/shl', $this->context(
            $patientId,
            $patientName,
            $practitionerId,
            $practitionerName,
            $organizationName,
            $organizationId
        ));
    }

    /**
     * @param string      $patientId
     * @param string      $patientName
     * @param string      $practitionerId
     * @param string      $practitionerName
     * @param string      $organizationName
     * @param string|null $organizationId
     *
     * @return array<string, string>
     */
    private function context($patientId, $patientName, $practitionerId, $practitionerName, $organizationName, $organizationId)
    {
        if ($organizationId === null) {
            $organizationId = $this->client->getConfig()->getOrganizationId();
        }
        if ($organizationId === null) {
            throw new InvalidArgumentException('An organization id is required: pass it or set organization_id.');
        }

        return array(
            'patient_id' => (string) $patientId,
            'patient_name' => (string) $patientName,
            'practitioner_id' => (string) $practitionerId,
            'practitioner_name' => (string) $practitionerName,
            'organization_id' => (string) $organizationId,
            'organization_name' => (string) $organizationName,
        );
    }
}
