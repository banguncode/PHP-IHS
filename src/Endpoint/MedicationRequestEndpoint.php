<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;

/**
 * MedicationRequest lookups.
 */
class MedicationRequestEndpoint extends ResourceEndpoint
{
    /**
     * GET /MedicationRequest?identifier=http://sys-ids.kemkes.go.id/prescription/national|{number}
     *
     * @param string $number National prescription number.
     *
     * @return Bundle
     */
    public function findByPrescriptionNumber($number)
    {
        return $this->searchByIdentifier(IdentifierSystem::PRESCRIPTION_NATIONAL, $number);
    }
}
