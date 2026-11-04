<?php

namespace BangunCode\Ihs\Fhir;

use BangunCode\Ihs\Exception\InvalidArgumentException;

/**
 * Identifier.system URIs defined by SATUSEHAT.
 *
 * Facility scoped identifiers follow http://sys-ids.kemkes.go.id/{kind}/{organizationId}. The
 * values below are taken verbatim from the SATUSEHAT Postman collections, including their scheme.
 */
final class IdentifierSystem
{
    const NIK = 'https://fhir.kemkes.go.id/id/nik';
    const NIK_IBU = 'https://fhir.kemkes.go.id/id/nik-ibu';
    const IHS_NUMBER = 'https://fhir.kemkes.go.id/id/ihs-number';
    const KK = 'https://fhir.kemkes.go.id/id/kk';
    const PASPOR = 'https://fhir.kemkes.go.id/id/paspor';
    const STR_KKI = 'https://fhir.kemkes.go.id/id/str-kki-number';
    const NAKES_HIS = 'https://fhir.kemkes.go.id/id/nakes-his-number';
    const KFA = 'http://sys-ids.kemkes.go.id/kfa';
    const PRESCRIPTION_NATIONAL = 'http://sys-ids.kemkes.go.id/prescription/national';
    const CREATOR = 'http://sys-ids.kemkes.go.id/creator';
    const AE_TITLE = 'http://sys-ids.kemkes.go.id/ae-title';
    const REFERRAL_NUMBER = 'http://sys-ids.kemkes.go.id/referral-number';
    const CLAIM_DISPENSE_TYPE = 'http://sys-ids.kemkes.go.id/claim-dispense-type';

    const BASE = 'http://sys-ids.kemkes.go.id/';

    private function __construct()
    {
    }

    /**
     * Facility scoped system, for example forOrganization('encounter', $orgId).
     *
     * Kinds seen in the SATUSEHAT collections: acsn, allergy, claim-number, clinicalimpression,
     * composition, condition, devicedispense, devicerequest, deviceusestatement, encounter,
     * location, medication, mrn, observation, organization, prescription, prescription-item,
     * QuestionnaireResponse, servicerequest, specimen, supplydelivery and supplyrequest. Use
     * device() for Device and diagnosticReport() for DiagnosticReport.
     *
     * @param string $kind
     * @param string $organizationId
     *
     * @return string
     */
    public static function forOrganization($kind, $organizationId)
    {
        if (!is_string($kind) || !preg_match('/^[A-Za-z][A-Za-z-]*$/', $kind)) {
            throw new InvalidArgumentException(sprintf('Invalid identifier kind "%s".', $kind));
        }

        return self::BASE . $kind . '/' . self::organization($organizationId);
    }

    /**
     * @param string $organizationId
     *
     * @return string https://sys-ids.kemkes.go.id/device/{organizationId}, the only facility scoped system served over https.
     */
    public static function device($organizationId)
    {
        return 'https://sys-ids.kemkes.go.id/device/' . self::organization($organizationId);
    }

    /**
     * @param string $organizationId
     * @param string $category lab or rad.
     *
     * @return string http://sys-ids.kemkes.go.id/diagnostic/{organizationId}/{category}
     */
    public static function diagnosticReport($organizationId, $category)
    {
        if (!in_array($category, array('lab', 'rad'), true)) {
            throw new InvalidArgumentException('DiagnosticReport identifier category must be "lab" or "rad".');
        }

        return self::BASE . 'diagnostic/' . self::organization($organizationId) . '/' . $category;
    }

    /**
     * @param string $organizationId
     *
     * @return string
     */
    private static function organization($organizationId)
    {
        if ((!is_string($organizationId) && !is_int($organizationId)) || (string) $organizationId === '') {
            throw new InvalidArgumentException('An organization id is required for facility scoped identifiers.');
        }

        return (string) $organizationId;
    }
}
