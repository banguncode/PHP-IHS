<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * DiagnosticReport lookups.
 */
class DiagnosticReportEndpoint extends ClinicalEndpoint
{
    /**
     * GET /DiagnosticReport?specimen={specimenId}&subject={patientId}
     *
     * @param string $specimenId
     * @param string $patientId
     *
     * @return Bundle
     */
    public function findBySpecimen($specimenId, $patientId)
    {
        return $this->search(array('specimen' => $specimenId, 'subject' => $patientId));
    }
}
