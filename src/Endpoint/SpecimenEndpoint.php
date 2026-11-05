<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;

/**
 * Specimen lookups.
 */
class SpecimenEndpoint extends SubjectEndpoint
{
    /**
     * GET /Specimen?request={serviceRequestId}
     *
     * @param string $serviceRequestId
     *
     * @return Bundle
     */
    public function findByRequest($serviceRequestId)
    {
        return $this->search(array('request' => $serviceRequestId));
    }

    /**
     * GET /Specimen?subject={patientId}&collector={practitionerId}
     *
     * @param string $patientId
     * @param string $practitionerId
     *
     * @return Bundle
     */
    public function findBySubjectAndCollector($patientId, $practitionerId)
    {
        return $this->search(array('subject' => $patientId, 'collector' => $practitionerId));
    }

    /**
     * GET /Specimen?subject={patientId}&collected={date}
     *
     * @param string $patientId
     * @param string $date      YYYY-MM-DD.
     *
     * @return Bundle
     */
    public function findBySubjectAndCollectedDate($patientId, $date)
    {
        return $this->search(array('subject' => $patientId, 'collected' => $date));
    }

    /**
     * GET /Specimen?identifier=http://sys-ids.kemkes.go.id/specimen-acsn|{accessionNumber}&request={serviceRequestId},
     * for specimens referred to another laboratory.
     *
     * @param string $accessionNumber
     * @param string $serviceRequestId
     *
     * @return Bundle
     */
    public function findByAccessionNumber($accessionNumber, $serviceRequestId)
    {
        return $this->searchByIdentifier(IdentifierSystem::SPECIMEN_ACSN, $accessionNumber, array('request' => $serviceRequestId));
    }
}
