<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * Immunization lookups.
 */
class ImmunizationEndpoint extends ResourceEndpoint
{
    /**
     * GET /Immunization?date={date}&patient={patientId}
     *
     * @param string      $patientId
     * @param string|null $date      Administration date, YYYY-MM-DD.
     *
     * @return Bundle
     */
    public function findByPatient($patientId, $date = null)
    {
        return $this->search(array('date' => $date, 'patient' => $patientId));
    }
}
