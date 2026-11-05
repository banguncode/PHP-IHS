<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;

/**
 * Coverage (insurance membership) lookups.
 */
class CoverageEndpoint extends ResourceEndpoint
{
    /**
     * GET /Coverage?patient={patientId}&identifier=https://sys-ids.kemkes.go.id/insurance-subscriber/{insurerOrganizationId}|{memberNumber}
     *
     * @param string $patientId
     * @param string $insurerOrganizationId Organization id of the insurer.
     * @param string $memberNumber          Empty to return every membership with that insurer.
     *
     * @return Bundle
     */
    public function findByPatient($patientId, $insurerOrganizationId, $memberNumber = '')
    {
        return $this->search(array(
            'patient' => $patientId,
            'identifier' => IdentifierSystem::insuranceSubscriber($insurerOrganizationId) . '|' . $memberNumber,
        ));
    }
}
