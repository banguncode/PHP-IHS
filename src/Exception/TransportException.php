<?php

namespace BangunCode\Ihs\Exception;

/**
 * Thrown when no HTTP response was received, for example on DNS, TLS or timeout failures.
 */
class TransportException extends \RuntimeException implements IhsException
{
}
