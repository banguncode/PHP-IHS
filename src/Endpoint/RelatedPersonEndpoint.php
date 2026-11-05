<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;

/**
 * RelatedPerson lookups.
 */
class RelatedPersonEndpoint extends ResourceEndpoint
{
    /**
     * GET /RelatedPerson?identifier=https://fhir.kemkes.go.id/id/nik|{nik}
     *
     * @param string $nik
     *
     * @return Bundle
     */
    public function findByNik($nik)
    {
        return $this->searchByIdentifier(IdentifierSystem::NIK, $nik);
    }
}
