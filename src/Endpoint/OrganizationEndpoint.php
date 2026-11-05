<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * Organization lookups.
 */
class OrganizationEndpoint extends ResourceEndpoint
{
    /**
     * GET /Organization?name={name}
     *
     * @param string $name
     *
     * @return Bundle
     */
    public function findByName($name)
    {
        return $this->search(array('name' => $name));
    }

    /**
     * GET /Organization?partof={organizationId}, the units below an organization.
     *
     * @param string $organizationId
     *
     * @return Bundle
     */
    public function findByPartOf($organizationId)
    {
        return $this->search(array('partof' => $organizationId));
    }
}
