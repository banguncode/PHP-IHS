<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;

/**
 * Patient lookups used to obtain the SATUSEHAT patient id (IHS number) before recording care.
 */
class PatientEndpoint extends ResourceEndpoint
{
    /**
     * GET /Patient?identifier=https://fhir.kemkes.go.id/id/nik|{nik}
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
     * GET /Patient?identifier=https://fhir.kemkes.go.id/id/nik-ibu|{nik}, for newborns registered under the mother.
     *
     * @param string $motherNik
     *
     * @return Bundle
     */
    public function findByMotherNik($motherNik)
    {
        return $this->searchByIdentifier(IdentifierSystem::NIK_IBU, $motherNik);
    }

    /**
     * GET /Patient?name={name}&birthdate={birthDate}&gender={gender}
     *
     * @param string $name
     * @param string $birthDate YYYY-MM-DD.
     * @param string $gender    male, female, other or unknown.
     *
     * @return Bundle
     */
    public function findByNameBirthDateGender($name, $birthDate, $gender)
    {
        return $this->search(array('name' => $name, 'birthdate' => $birthDate, 'gender' => $gender));
    }

    /**
     * GET /Patient?identifier=https://fhir.kemkes.go.id/id/nik|{nik}&name={name}&birthdate={birthDate}
     *
     * @param string $name
     * @param string $birthDate YYYY-MM-DD.
     * @param string $nik
     *
     * @return Bundle
     */
    public function findByNameBirthDateNik($name, $birthDate, $nik)
    {
        return $this->searchByIdentifier(IdentifierSystem::NIK, $nik, array('name' => $name, 'birthdate' => $birthDate));
    }
}
