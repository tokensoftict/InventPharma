<?php

namespace App\Services\Backup\Exceptions;

class ConfigurationException extends BackupException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 'configuration', false, $previous);
    }
}
