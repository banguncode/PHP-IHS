<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * QuestionnaireResponse lookups.
 */
class QuestionnaireResponseEndpoint extends ResourceEndpoint
{
    /**
     * GET /QuestionnaireResponse?encounter={encounterId}&patient={patientId}
     *
     * @param string $encounterId
     * @param string $patientId
     *
     * @return Bundle
     */
    public function findByEncounter($encounterId, $patientId)
    {
        return $this->search(array('encounter' => $encounterId, 'patient' => $patientId));
    }
}
