<?php

namespace App\Services\Backup\Exceptions;

class VerificationFailedException extends BackupException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 'verification_failed', true);
    }
}
