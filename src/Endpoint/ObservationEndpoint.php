<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * Observation lookups.
 */
class ObservationEndpoint extends ClinicalEndpoint
{
    /**
     * GET /Observation?based-on={serviceRequestId}&subject={patientId}, the results of an order.
     *
     * @param string $serviceRequestId
     * @param string $patientId
     *
     * @return Bundle
     */
    public function findByBasedOn($serviceRequestId, $patientId)
    {
        return $this->search(array('based-on' => $serviceRequestId, 'subject' => $patientId));
    }
}
