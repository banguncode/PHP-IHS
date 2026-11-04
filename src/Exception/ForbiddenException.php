<?php

namespace BangunCode\Ihs\Exception;

/**
 * Thrown when access is refused, either by the gateway (HTTP 403) or by a business, consent
 * or permission rule.
 */
class ForbiddenException extends ApiException
{
}
