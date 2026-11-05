<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * MedicationDispense lookups.
 */
class MedicationDispenseEndpoint extends SubjectEndpoint
{
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

    /**
     * GET /MedicationDispense?subject={patientId}&prescription={medicationRequestId}
     *
     * @param string $patientId
     * @param string $medicationRequestId
     *
     * @return Bundle
     */
    public function findByPrescription($patientId, $medicationRequestId)
    {
        return $this->search(array('subject' => $patientId, 'prescription' => $medicationRequestId));
    }
}
