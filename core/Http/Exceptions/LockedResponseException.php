<?php

namespace EApp\Http\Exceptions;

use RuntimeException;

/**
 * LockedResponseException
 *
 * Exception used for when a response is attempted to be modified while its locked
 */
class LockedResponseException extends RuntimeException implements HttpExceptionInterface
{
}