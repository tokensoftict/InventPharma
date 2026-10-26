<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Services\Backup\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs one backup attempt in the background.
 *
 * Retries are managed by BackupService (increasing delays), not by the queue, so the
 * queue-level $tries is 1. The shared "database" queue has retry_after=90; because a
 * backup can run for minutes, correctness does not rely on it: BackupService holds an
 * atomic lock and only executes pending/stale backups, so a re-delivered job is a no-op.
 */
class RunBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public bool $failOnTimeout = false;

    public function __construct(public int $backupId)
    {
        $this->timeout = (int) config('backup-manager.job_timeout', 7200);
    }

    public function handle(BackupService $service): void
    {
        $backup = Backup::find($this->backupId);

        if ($backup) {
            $service->run($backup);
        }
    }

    public function failed(?\Throwable $e): void
    {
        // Worker killed / timed out: mark it so the UI doesn't show "running" forever.
        $backup = Backup::find($this->backupId);
        if ($backup && $backup->status === Backup::STATUS_RUNNING) {
            app(BackupService::class)->recoverStale();
        }
    }
}
