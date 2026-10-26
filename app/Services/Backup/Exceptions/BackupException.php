<?php

namespace App\Services\Backup\Exceptions;

use RuntimeException;

/**
 * Base class for all expected Backup Manager failures.
 * The "retriable" flag tells the runner whether another attempt can possibly succeed.
 */
class BackupException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode = 'backup_error', public readonly bool $retriable = true, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
