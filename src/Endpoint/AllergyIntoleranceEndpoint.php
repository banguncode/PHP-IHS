<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * AllergyIntolerance lookups.
 */
class AllergyIntoleranceEndpoint extends ResourceEndpoint
{
    /**
     * GET /AllergyIntolerance?patient={patientId}&code={code}
     *
     * @param string      $patientId
     * @param string|null $code      Allergen code, for example a SNOMED CT code.
     *
     * @return Bundle
     */
    public function findByPatient($patientId, $code = null)
    {
        return $this->search(array('patient' => $patientId, 'code' => $code));
    }
}
