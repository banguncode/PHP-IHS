<?php

namespace BangunCode\Ihs\Exception;

/**
 * Thrown when a caller passes a value the library cannot use.
 */
class InvalidArgumentException extends \InvalidArgumentException implements IhsException
{
}
