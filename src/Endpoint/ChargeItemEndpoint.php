<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * ChargeItem lookups.
 */
class ChargeItemEndpoint extends ResourceEndpoint
{
    /**
     * GET /ChargeItem?account={accountId}&subject={patientId}
     *
     * @param string $accountId
     * @param string $patientId
     *
     * @return Bundle
     */
    public function findByAccount($accountId, $patientId)
    {
        return $this->search(array('account' => $accountId, 'subject' => $patientId));
    }
}
