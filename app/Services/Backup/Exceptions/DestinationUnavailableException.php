<?php

namespace App\Services\Backup\Exceptions;

class DestinationUnavailableException extends BackupException
{
    public function __construct(string $detail = '', ?\Throwable $previous = null)
    {
        parent::__construct(trim('Backup destination unavailable. ' . $detail), 'destination_unavailable', true, $previous);
    }
}
