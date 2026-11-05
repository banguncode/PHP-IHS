<?php

namespace BangunCode\Ihs\Fhir;

/**
 * FHIR resource types used by the SATUSEHAT Postman collections.
 */
final class ResourceType
{
    const ACCOUNT = 'Account';
    const ALLERGY_INTOLERANCE = 'AllergyIntolerance';
    const CARE_PLAN = 'CarePlan';
    const CHARGE_ITEM = 'ChargeItem';
    const CLAIM = 'Claim';
    const CLAIM_RESPONSE = 'ClaimResponse';
    const CLINICAL_IMPRESSION = 'ClinicalImpression';
    const COMPOSITION = 'Composition';
    const CONDITION = 'Condition';
    const COVERAGE = 'Coverage';
    const COVERAGE_ELIGIBILITY_REQUEST = 'CoverageEligibilityRequest';
    const COVERAGE_ELIGIBILITY_RESPONSE = 'CoverageEligibilityResponse';
    const DEVICE = 'Device';
    const DEVICE_DISPENSE = 'DeviceDispense';
    const DEVICE_REQUEST = 'DeviceRequest';
    const DEVICE_USE_STATEMENT = 'DeviceUseStatement';
    const DIAGNOSTIC_REPORT = 'DiagnosticReport';
    const DOCUMENT_REFERENCE = 'DocumentReference';
    const ENCOUNTER = 'Encounter';
    const EPISODE_OF_CARE = 'EpisodeOfCare';
    const FAMILY_MEMBER_HISTORY = 'FamilyMemberHistory';
    const GOAL = 'Goal';
    const IMAGING_STUDY = 'ImagingStudy';
    const IMMUNIZATION = 'Immunization';
    const INVOICE = 'Invoice';
    const LOCATION = 'Location';
    const MEDICATION = 'Medication';
    const MEDICATION_ADMINISTRATION = 'MedicationAdministration';
    const MEDICATION_DISPENSE = 'MedicationDispense';
    const MEDICATION_REQUEST = 'MedicationRequest';
    const MEDICATION_STATEMENT = 'MedicationStatement';
    const NUTRITION_ORDER = 'NutritionOrder';
    const OBSERVATION = 'Observation';
    const ORGANIZATION = 'Organization';
    const PATIENT = 'Patient';
    const PAYMENT_NOTICE = 'PaymentNotice';
    const PAYMENT_RECONCILIATION = 'PaymentReconciliation';
    const PRACTITIONER = 'Practitioner';
    const PROCEDURE = 'Procedure';
    const QUESTIONNAIRE_RESPONSE = 'QuestionnaireResponse';
    const RELATED_PERSON = 'RelatedPerson';
    const RISK_ASSESSMENT = 'RiskAssessment';
    const SERVICE_REQUEST = 'ServiceRequest';
    const SPECIMEN = 'Specimen';
    const SUPPLY_DELIVERY = 'SupplyDelivery';
    const SUPPLY_REQUEST = 'SupplyRequest';

    private function __construct()
    {
    }

    /**
     * @return string[]
     */
    public static function all()
    {
        $reflection = new \ReflectionClass(__CLASS__);

        return array_values($reflection->getConstants());
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isKnown($type)
    {
        return in_array($type, self::all(), true);
    }

    /**
     * Whether a string is shaped like a FHIR resource type name, known to this list or not.
     *
     * @param string $type
     *
     * @return bool
     */
    public static function isValidName($type)
    {
        return is_string($type) && preg_match('/^[A-Z][A-Za-z]+$/', $type) === 1;
    }
}
