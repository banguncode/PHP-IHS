<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;

/**
 * Claim lookups.
 */
class ClaimEndpoint extends ResourceEndpoint
{
    /**
     * GET /Claim?identifier=http://sys-ids.kemkes.go.id/claim-number/{insurerOrganizationId}|{sepNumber}
     *
     * @param string $insurerOrganizationId Organization id of the insurer.
     * @param string $sepNumber             Eligibility (SEP) number.
     *
     * @return Bundle
     */
    public function findBySepNumber($insurerOrganizationId, $sepNumber)
    {
        return $this->searchByIdentifier(IdentifierSystem::forOrganization('claim-number', $insurerOrganizationId), $sepNumber);
    }
}
