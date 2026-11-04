<?php

namespace BangunCode\Ihs\Exception;

/**
 * Thrown when data is withheld by consent or privacy rules, for example a patient who has not
 * opted in. SATUSEHAT may report this as HTTP 400 (issue code "forbidden") or as HTTP 200 with an
 * OperationOutcome whose issue code is "suppressed".
 */
class ConsentException extends ForbiddenException
{
}
