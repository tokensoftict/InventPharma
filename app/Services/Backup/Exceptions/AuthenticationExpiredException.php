<?php

namespace App\Services\Backup\Exceptions;

class AuthenticationExpiredException extends BackupException
{
    public function __construct(string $message = 'Google Drive authentication expired. Please reconnect the account.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 'auth_expired', false, $previous);
    }
}
