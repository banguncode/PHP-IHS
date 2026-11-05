<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * EpisodeOfCare lookups.
 */
class EpisodeOfCareEndpoint extends SubjectEndpoint
{
    /**
     * GET /EpisodeOfCare?subject={patientId}&organization={organizationId}&care-manager={practitionerId}
     *
     * @param string $patientId
     * @param string $organizationId
     * @param string $practitionerId
     *
     * @return Bundle
     */
    public function findByCareManager($patientId, $organizationId, $practitionerId)
    {
        return $this->search(array(
            'subject' => $patientId,
            'organization' => $organizationId,
            'care-manager' => $practitionerId,
        ));
    }
}
