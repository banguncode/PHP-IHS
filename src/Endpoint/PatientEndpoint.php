<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;

/**
 * Patient lookups used to obtain the SATUSEHAT patient id (IHS number) before recording care.
 *
 * Creating a patient by NIK, or a newborn by the mother's NIK, is a regular create(). Updating
 * demographics is a patch() whose operations must start with "test" operations on name, gender
 * and birthDate; SATUSEHAT answers that request with its own JSON envelope instead of FHIR.
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
     * GET /Patient?identifier=https://fhir.kemkes.go.id/id/nik-ibu|{nik}&birthdate={birthDate}, newborns
     * registered under the mother.
     *
     * @param string      $motherNik
     * @param string|null $birthDate YYYY-MM-DD.
     *
     * @return Bundle
     */
    public function findByMotherNik($motherNik, $birthDate = null)
    {
        return $this->searchByIdentifier(IdentifierSystem::NIK_IBU, $motherNik, array('birthdate' => $birthDate));
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
     * GET /Patient?name={name}&birthdate={birthDate}&identifier=https://fhir.kemkes.go.id/id/nik|{nik}
     *
     * @param string $name
     * @param string $birthDate YYYY-MM-DD.
     * @param string $nik
     *
     * @return Bundle
     */
    public function findByNameBirthDateNik($name, $birthDate, $nik)
    {
        return $this->search(array(
            'name' => $name,
            'birthdate' => $birthDate,
            'identifier' => IdentifierSystem::NIK . '|' . $nik,
        ));
    }

    /**
     * GET /Patient?name={name}&identifier=https://fhir.kemkes.go.id/id/nik|{nik}
     *
     * @param string $name
     * @param string $nik
     *
     * @return Bundle
     */
    public function findByNameNik($name, $nik)
    {
        return $this->search(array('name' => $name, 'identifier' => IdentifierSystem::NIK . '|' . $nik));
    }
}
