<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;

/**
 * Practitioner lookups.
 */
class PractitionerEndpoint extends ResourceEndpoint
{
    /**
     * GET /Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|{nik}
     *
     * @param string $nik
     *
     * @return Bundle
     */
    public function findByNik($nik)
    {
        return $this->searchByIdentifier(IdentifierSystem::NIK, $nik);
    }

    /**
     * GET /Practitioner?name={name}&gender={gender}&birthdate={birthDate}
     *
     * @param string $name
     * @param string $birthDate YYYY-MM-DD.
     * @param string $gender    male or female.
     *
     * @return Bundle
     */
    public function findByNameBirthDateGender($name, $birthDate, $gender)
    {
        return $this->search(array('name' => $name, 'gender' => $gender, 'birthdate' => $birthDate));
    }

    /**
     * GET /Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|{nik}&name={name}
     *
     * @param string $name
     * @param string $nik
     *
     * @return Bundle
     */
    public function findByNameNik($name, $nik)
    {
        return $this->searchByIdentifier(IdentifierSystem::NIK, $nik, array('name' => $name));
    }
}
