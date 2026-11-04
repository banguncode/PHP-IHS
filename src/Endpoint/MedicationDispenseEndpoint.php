<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * MedicationDispense lookups.
 */
class MedicationDispenseEndpoint extends ResourceEndpoint
{
    /**
     * GET /MedicationDispense?subject={patientId}, the medication history used during anamnesis.
     *
     * @param string $patientId
     *
     * @return Bundle
     */
    public function findBySubject($patientId)
    {
        return $this->search(array('subject' => $patientId));
    }

    /**
     * GET /MedicationDispense?context={encounterId}&subject={patientId}
     *
     * @param string $encounterId
     * @param string $patientId
     *
     * @return Bundle
     */
    public function findByEncounter($encounterId, $patientId)
    {
        return $this->search(array('context' => $encounterId, 'subject' => $patientId));
    }
}
