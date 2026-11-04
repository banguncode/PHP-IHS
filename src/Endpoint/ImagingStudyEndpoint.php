<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Exception\InvalidArgumentException;
use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;

/**
 * ImagingStudy, created by the DICOM Router after a modality sends its images.
 */
class ImagingStudyEndpoint extends ResourceEndpoint
{
    /**
     * GET /ImagingStudy?identifier=http://sys-ids.kemkes.go.id/acsn/{organizationId}|{accessionNumber}
     *
     * @param string      $accessionNumber
     * @param string|null $organizationId  Defaults to the organization_id configuration option.
     *
     * @return Bundle
     */
    public function findByAccessionNumber($accessionNumber, $organizationId = null)
    {
        if ($organizationId === null) {
            $organizationId = $this->client->getConfig()->getOrganizationId();
        }
        if ($organizationId === null) {
            throw new InvalidArgumentException('An organization id is required: pass it or set organization_id.');
        }

        return $this->searchByIdentifier(IdentifierSystem::forOrganization('acsn', $organizationId), $accessionNumber);
    }
}
