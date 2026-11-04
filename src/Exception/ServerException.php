<?php

namespace BangunCode\Ihs\Exception;

/**
 * Thrown on HTTP 5xx, for example "no healthy upstream" while the platform is unavailable.
 */
class ServerException extends ApiException
{
}
