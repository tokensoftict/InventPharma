<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Services\Backup\Exceptions\BackupException;
use App\Services\Backup\RestoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;

/** Restores a backup in the background. Never retried: a restore must not run twice by accident. */
class RestoreBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $backupId, public ?int $userId = null, public ?string $encryptedPassword = null)
    {
        $this->timeout = (int) config('backup-manager.job_timeout', 7200);
    }

    public function handle(RestoreService $restore): void
    {
        $backup = Backup::find($this->backupId);
        if (! $backup) {
            return;
        }

        try {
            $password = $this->encryptedPassword ? Crypt::decryptString($this->encryptedPassword) : null;
            $restore->restore($backup, $this->userId, $password);
        } catch (BackupException) {
            // already logged + status recorded by RestoreService
        }
    }
}
