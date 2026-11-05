<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * PaymentReconciliation lookups.
 */
class PaymentReconciliationEndpoint extends ResourceEndpoint
{
    /**
     * GET /PaymentReconciliation?detail-request={claimId}
     *
     * @param string $claimId
     *
     * @return Bundle
     */
    public function findByClaim($claimId)
    {
        return $this->search(array('detail-request' => $claimId));
    }
}
