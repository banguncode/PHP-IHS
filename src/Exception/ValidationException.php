<?php

namespace BangunCode\Ihs\Exception;

/**
 * Thrown when a payload is rejected (HTTP 400 or 422): unparseable JSON, a profile rule
 * violation, a missing reference target, mismatched ids or a duplicate resource.
 */
class ValidationException extends ApiException
{
}
