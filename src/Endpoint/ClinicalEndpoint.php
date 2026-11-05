<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * Endpoint for clinical records searched by patient, by encounter or by both.
 */
class ClinicalEndpoint extends SubjectEndpoint
{
    /**
     * GET /{type}?subject={patientId}&encounter={encounterId}
     *
     * @param string      $patientId
     * @param string|null $encounterId Narrows the search to one encounter.
     *
     * @return Bundle
     */
    public function findBySubject($patientId, $encounterId = null)
    {
        return $this->search(array('subject' => $patientId, 'encounter' => $encounterId));
    }

    /**
     * GET /{type}?encounter={encounterId}
     *
     * @param string $encounterId
     *
     * @return Bundle
     */
    public function findByEncounter($encounterId)
    {
        return $this->search(array('encounter' => $encounterId));
    }
}
