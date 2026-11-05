<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;

/**
 * CoverageEligibilityRequest, the eligibility (SEP) request sent to an insurer.
 */
class CoverageEligibilityRequestEndpoint extends ResourceEndpoint
{
    /**
     * GET /CoverageEligibilityRequest?identifier=https://sys-ids.kemkes.go.id/insurance-number/{insurerOrganizationId}|{number}&patient={patientId}
     *
     * @param string $insurerOrganizationId Organization id of the insurer.
     * @param string $number
     * @param string $patientId
     *
     * @return Bundle
     */
    public function findByIdentifier($insurerOrganizationId, $number, $patientId)
    {
        return $this->searchByIdentifier(
            IdentifierSystem::insuranceNumber($insurerOrganizationId),
            $number,
            array('patient' => $patientId)
        );
    }

    /**
     * GET /CoverageEligibilityRequest/{id}/$everything, the request with its related resources.
     *
     * @param string $id
     *
     * @return Bundle
     */
    public function everything($id)
    {
        return new Bundle((array) $this->operation($id, '$everything'));
    }
}
