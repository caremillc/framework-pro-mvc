<?php declare(strict_types=1);

namespace Careminate\Exceptions;

use Exception;

/**
 * Exception for authentication & authorization errors.
 */
class AuthException extends Exception
{
    public function __construct(string $message = "Unauthorized", int $code = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
