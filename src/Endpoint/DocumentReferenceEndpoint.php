<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;

/**
 * DocumentReference, used by e-prescribing to obtain and look up the national prescription number.
 */
class DocumentReferenceEndpoint extends ResourceEndpoint
{
    /**
     * GET /DocumentReference?identifier=http://sys-ids.kemkes.go.id/prescription/national|{number}
     *
     * @param string $number         National prescription number, for example 241203-DDG2BM2.
     * @param bool   $includeRelated Adds _include=DocumentReference:related so the attached
     *                               MedicationRequest, Observation and MedicationDispense resources
     *                               are returned in the same Bundle.
     *
     * @return Bundle
     */
    public function findByPrescriptionNumber($number, $includeRelated = false)
    {
        $parameters = $includeRelated ? array('_include' => 'DocumentReference:related') : array();

        return $this->searchByIdentifier(IdentifierSystem::PRESCRIPTION_NATIONAL, $number, $parameters);
    }
}
