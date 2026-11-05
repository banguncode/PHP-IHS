<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * Endpoint for resource types searched by patient through the subject parameter.
 */
class SubjectEndpoint extends ResourceEndpoint
{
    /**
     * GET /{type}?subject={patientId}
     *
     * @param string $patientId
     *
     * @return Bundle
     */
    public function findBySubject($patientId)
    {
        return $this->search(array('subject' => $patientId));
    }
}
