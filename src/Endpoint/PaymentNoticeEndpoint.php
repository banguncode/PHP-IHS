<?php

namespace BangunCode\Ihs\Endpoint;

use BangunCode\Ihs\Fhir\Bundle;

/**
 * PaymentNotice lookups.
 */
class PaymentNoticeEndpoint extends ResourceEndpoint
{
    /**
     * GET /PaymentNotice?request={claimId}
     *
     * @param string $claimId
     *
     * @return Bundle
     */
    public function findByClaim($claimId)
    {
        return $this->search(array('request' => $claimId));
    }
}
