<?php

namespace App\Services\Backup\Exceptions;

class InsufficientStorageException extends BackupException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 'insufficient_storage', true);
    }
}
