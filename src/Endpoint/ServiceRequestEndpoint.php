<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Exception\InvalidArgumentException;
use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;

/**
 * ServiceRequest lookups.
 */
class ServiceRequestEndpoint extends ClinicalEndpoint
{
    /**
     * GET /ServiceRequest?identifier=http://sys-ids.kemkes.go.id/acsn/{organizationId}|{accessionNumber}
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

    /**
     * GET /ServiceRequest?identifier=http://sys-ids.kemkes.go.id/referral-number|{referralNumber}
     *
     * @param string $referralNumber National referral number.
     *
     * @return Bundle
     */
    public function findByReferralNumber($referralNumber)
    {
        return $this->searchByIdentifier(IdentifierSystem::REFERRAL_NUMBER, $referralNumber);
    }
}
