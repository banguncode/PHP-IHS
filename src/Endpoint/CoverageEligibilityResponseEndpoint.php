<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * CoverageEligibilityResponse lookups.
 */
class CoverageEligibilityResponseEndpoint extends ResourceEndpoint
{
    /**
     * GET /CoverageEligibilityResponse?request={coverageEligibilityRequestId}
     *
     * @param string $coverageEligibilityRequestId
     *
     * @return Bundle
     */
    public function findByRequest($coverageEligibilityRequestId)
    {
        return $this->search(array('request' => $coverageEligibilityRequestId));
    }
}
