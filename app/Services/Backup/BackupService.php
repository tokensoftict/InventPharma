<?php

namespace App\Services\Backup;

use App\Jobs\RunBackupJob;
use App\Models\Backup;
use App\Models\BackupDestination;
use App\Models\BackupSetting;
use App\Services\Backup\Dump\DatabaseDumpService;
use App\Services\Backup\Exceptions\AuthenticationExpiredException;
use App\Services\Backup\Exceptions\BackupException;
use App\Services\Backup\Exceptions\ConfigurationException;
use App\Services\Backup\Exceptions\DestinationUnavailableException;
use App\Services\Backup\Exceptions\InsufficientStorageException;
use App\Services\Backup\Exceptions\VerificationFailedException;
use App\Services\Backup\Support\BackupEncryptor;
use App\Services\Backup\Support\BackupVerifier;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Orchestrates a backup: dump -> compress -> verify -> (encrypt) -> checksum ->
 * store -> confirm -> record.
 *
 * A backup is marked successful ONLY after the destination has confirmed the stored
 * copy. Every failure path is caught: a backup can never throw into the application.
 */
class BackupService
{
    public const LOCK = 'backup-manager-run';

    public function __construct(
        protected BackupDestinationManager $drivers,
        protected DatabaseDumpService $dumps,
        protected BackupEncryptor $encryptor,
        protected BackupVerifier $verifier,
        protected BackupLogger $log,
        protected BackupNotifier $notifier,
        protected RetentionService $retention,
        protected StorageMonitor $storage,
    ) {
    }

    /* ------------------------------------------------------------ creation */

    /**
     * Create a pending backup row.
     *
     * Scheduled runs pass a $scheduleKey; (destination_id, schedule_key) is unique, so a
     * scheduler firing twice - or two servers - still yields exactly one backup.
     *
     * @return array{0:Backup,1:bool} [backup, wasCreated]
     */
    public function createPending(BackupDestination $destination, string $trigger = Backup::TRIGGER_MANUAL, ?int $userId = null, ?string $scheduleKey = null): array
    {
        if ($scheduleKey) {
            $existing = Backup::where('destination_id', $destination->id)->where('schedule_key', $scheduleKey)->first();
            if ($existing) {
                return [$existing, false];
            }
        } else {
            $active = Backup::where('destination_id', $destination->id)->whereIn('status', [Backup::STATUS_PENDING, Backup::STATUS_RUNNING])
                ->where('trigger', '!=', Backup::TRIGGER_SAFETY)->exists();
            if ($active && $trigger !== Backup::TRIGGER_SAFETY) {
                throw new BackupException('A backup is already pending or running for this destination.', 'already_running', false);
            }
        }

        try {
            $backup = Backup::create([
                'type'           => 'database',
                'destination_id' => $destination->id,
                'trigger'        => $trigger,
                'schedule_key'   => $scheduleKey,
                'status'         => Backup::STATUS_PENDING,
                'encrypted'      => (bool) BackupSetting::current()->encryption_enabled,
                'created_by'     => $userId,
            ]);
        } catch (QueryException $e) {
            if ($scheduleKey && in_array($e->getCode(), ['23000', 23000, '23505'], true)) {
                return [Backup::where('destination_id', $destination->id)->where('schedule_key', $scheduleKey)->firstOrFail(), false];
            }
            throw $e;
        }

        $this->log->event($backup, 'backup_queued', "Backup queued ({$trigger}).");

        return [$backup, true];
    }

    public function dispatch(Backup $backup, int $delaySeconds = 0): void
    {
        $job = RunBackupJob::dispatch($backup->id);
        if ($delaySeconds > 0) {
            $job->delay(now()->addSeconds($delaySeconds));
        }
    }

    /** Re-queue a failed/cancelled backup from scratch (manual "Retry"). */
    public function retry(Backup $backup): Backup
    {
        if (! in_array($backup->status, [Backup::STATUS_FAILED, Backup::STATUS_CANCELLED], true)) {
            throw new BackupException('Only failed backups can be retried.', 'invalid_state', false);
        }

        $backup->forceFill(['status' => Backup::STATUS_PENDING, 'attempts' => 0, 'error_message' => null, 'error_code' => null, 'completed_at' => null, 'next_retry_at' => null, 'duration_seconds' => null])->save();
        $this->log->event($backup, 'retry_started', 'Manual retry requested.');
        $this->dispatch($backup);

        return $backup;
    }

    /* ----------------------------------------------------------------- run */

    /**
     * Execute one attempt. Never throws.
     */
    public function run(Backup $backup, bool $allowRetry = true): Backup
    {
        $backup->refresh();

        // Idempotency: only a pending (or stale "running") backup is executed.
        if ($backup->status === Backup::STATUS_RUNNING && ! $this->isStale($backup)) {
            return $backup;
        }
        if (! in_array($backup->status, [Backup::STATUS_PENDING, Backup::STATUS_RUNNING], true)) {
            return $backup;
        }

        // Only one backup at a time on this host (protects the DB and the disk).
        $lock = Cache::lock(self::LOCK, (int) config('backup-manager.job_timeout', 7200));
        if (! $lock->get()) {
            $this->log->event($backup, 'backup_deferred', 'Another backup is running; this one will start shortly.');
            if ($allowRetry) {
                $this->dispatch($backup, 60);
            }

            return $backup;
        }

        try {
            return $this->execute($backup, $allowRetry);
        } finally {
            $lock->release();
        }
    }

    protected function execute(Backup $backup, bool $allowRetry): Backup
    {
        $destination = $backup->destination;
        $startedAt = $backup->started_at ?? now();

        $backup->forceFill([
            'status'       => Backup::STATUS_RUNNING,
            'started_at'   => $startedAt,
            'attempts'     => $backup->attempts + 1,
            'heartbeat_at' => now(),
            'next_retry_at' => null,
            'error_message' => null,
        ])->save();

        $this->log->event($backup, 'backup_started', "Backup started (attempt {$backup->attempts}).");

        $stage = null;

        try {
            if (! $destination) {
                throw new ConfigurationException('The backup destination no longer exists.');
            }
            if (! $destination->enabled) {
                throw new ConfigurationException("Destination \"{$destination->name}\" is disabled.");
            }

            $settings = BackupSetting::current();
            $driver = $this->drivers->driver($destination);

            // 1. Destination reachable / writable / enough space (before doing any work)
            $required = $this->storage->requiredBytes($this->dumps->estimateBytes());
            $driver->validate($destination, $required);
            $this->assertStagingSpace($required * ($settings->encryption_enabled ? 2 : 1));
            $destination->markStatus(BackupDestination::STATUS_READY);
            $this->warnIfLowStorage($destination);

            // 2. Dump + compress (stream into gzip)
            $stage = $this->stageDirectory($backup);
            $base = $this->filename($settings);
            $gzPath = $stage . DIRECTORY_SEPARATOR . $base;
            $beat = fn () => $this->heartbeat($backup);

            $meta = $this->dumps->dumper()->dumpToGzip($gzPath, $beat);
            $this->log->event($backup, 'dump_generated', 'Database dump generated.', 'info', ['tool' => $meta['tool'] ?? null, 'tables' => $meta['tables'] ?? null]);
            $this->log->event($backup, 'compression_completed', 'Dump compressed (gzip).', 'info', ['bytes' => filesize($gzPath)]);
            $this->heartbeat($backup);

            // 3. Verify the dump itself before spending time on encrypt/upload
            $details = $this->verifier->verifyGzipDump($gzPath, $this->dumps->databaseName());
            $this->log->event($backup, 'dump_verified', 'Dump verified (readable gzip, schema present, complete).');

            // 4. Optional encryption
            $storedPath = $gzPath;
            $storedName = $base;
            $encrypted = false;
            if ($settings->encryption_enabled) {
                if (! $settings->encryption_password) {
                    throw new ConfigurationException('Encryption is enabled but no encryption password is configured.');
                }
                $storedName = $base . '.enc';
                $storedPath = $gzPath . '.enc';
                $this->encryptor->encryptFile($gzPath, $storedPath, $settings->encryption_password);
                $this->encryptor->decryptFile($storedPath, null, $settings->encryption_password); // authenticated read-back
                @unlink($gzPath);
                $encrypted = true;
                $details['encryption'] = 'verified';
                $this->log->event($backup, 'encryption_completed', 'Backup encrypted and verified.');
                $this->heartbeat($backup);
            }

            // 5. Checksum of exactly what will be stored
            $sha256 = hash_file('sha256', $storedPath);
            $size = filesize($storedPath);
            if (! $sha256 || ! $size) {
                throw new VerificationFailedException('Final backup file is empty or unreadable.');
            }
            $details['sha256'] = $sha256;

            // 6. Store, then have the destination confirm
            $this->log->event($backup, 'upload_started', "Storing backup on {$destination->name}.");
            $stored = $driver->store($destination, $storedPath, $storedName, function () use ($beat) {
                $beat();
            });

            $backup->forceFill([
                'filename'        => $storedName,
                'path'            => $stored->path,
                'external_id'     => $stored->externalId,
                'size_bytes'      => $size,
                'checksum_sha256' => $sha256,
                'encrypted'       => $encrypted,
            ])->save();

            if (! $driver->verifyStored($destination, $backup->fresh())) {
                try {
                    $driver->delete($destination, $backup->fresh());
                } catch (\Throwable) {
                }
                throw new VerificationFailedException('The destination could not confirm the stored backup (size/checksum mismatch).');
            }
            $this->log->event($backup, 'upload_completed', 'Destination confirmed the stored backup.');

            // 7. Only now is it a successful backup
            $details['stored_verified'] = true;
            $backup->forceFill([
                'status'               => Backup::STATUS_SUCCESSFUL,
                'verification_status'  => 'passed',
                'verification_details' => $details,
                'meta'                 => $meta,
                'completed_at'         => now(),
                'duration_seconds'     => max(0, now()->diffInSeconds($startedAt, true)),
                'error_message'        => null,
                'error_code'           => null,
                'stage_path'           => null,
            ])->save();

            $destination->forceFill(['last_successful_backup_at' => now()])->save();
            $this->log->event($backup, 'backup_completed', "Backup completed ({$backup->humanSize()}).");
            $this->notifier->notify('backup_success', 'Backup completed', "Backup {$backup->filename} ({$backup->humanSize()}) was stored on {$destination->name}.");

            $this->cleanStage($stage);

            // Retention is housekeeping: it can never fail the backup that just succeeded.
            try {
                $this->retention->apply($destination);
            } catch (\Throwable $e) {
                $this->log->event($backup, 'retention_failed', $e->getMessage(), 'warning');
            }
        } catch (\Throwable $e) {
            $this->fail($backup, $destination, $e, $allowRetry, $stage);
        }

        return $backup->fresh();
    }

    /* ------------------------------------------------------------- failure */

    protected function fail(Backup $backup, ?BackupDestination $destination, \Throwable $e, bool $allowRetry, ?string $stage): void
    {
        $retriable = $e instanceof BackupException ? $e->retriable : true;
        $code = $e instanceof BackupException ? $e->errorCode : 'unexpected_error';
        $message = $e instanceof BackupException ? $e->getMessage() : 'Unexpected error: ' . mb_substr($e->getMessage(), 0, 300);

        // Keep the failed attempt's remote artefact only if it is verified; otherwise discard staging.
        $this->cleanStage($stage);

        if ($destination) {
            if ($e instanceof DestinationUnavailableException) {
                $destination->markStatus(BackupDestination::STATUS_UNAVAILABLE, $message);
            } elseif ($e instanceof AuthenticationExpiredException) {
                $destination->markStatus(BackupDestination::STATUS_AUTH_EXPIRED, $message);
            }
            $destination->forceFill(['last_failure_at' => now()])->save();
        }

        $delays = (array) config('backup-manager.retry_delays', [60, 300, 900]);
        $canRetry = $allowRetry && $retriable && $backup->attempts <= count($delays);

        if ($canRetry) {
            $delay = $delays[$backup->attempts - 1];
            $backup->forceFill([
                'status'        => Backup::STATUS_PENDING,
                'error_message' => $message,
                'error_code'    => $code,
                'next_retry_at' => now()->addSeconds($delay),
                'heartbeat_at'  => null,
                'stage_path'    => null,
            ])->save();
            $this->log->event($backup, 'retry_scheduled', "{$message} Retrying in {$delay}s.", 'warning', ['attempt' => $backup->attempts]);
            $this->dispatch($backup, $delay);

            return;
        }

        $backup->forceFill([
            'status'           => Backup::STATUS_FAILED,
            'error_message'    => $message,
            'error_code'       => $code,
            'completed_at'     => now(),
            'duration_seconds' => $backup->started_at ? max(0, now()->diffInSeconds($backup->started_at, true)) : null,
            'verification_status' => $e instanceof VerificationFailedException ? 'failed' : $backup->verification_status,
            'stage_path'       => null,
        ])->save();

        $this->log->event($backup, 'backup_failed', $message, 'error', ['code' => $code, 'attempts' => $backup->attempts]);

        $event = match (true) {
            $e instanceof DestinationUnavailableException => 'destination_unavailable',
            $e instanceof AuthenticationExpiredException  => 'auth_expired',
            default                                       => 'backup_failed',
        };
        $subject = match ($event) {
            'destination_unavailable' => 'Backup destination unavailable',
            'auth_expired'            => 'Google Drive authentication expired',
            default                   => 'Backup failed',
        };
        $this->notifier->notify($event, $subject, $message);
    }

    /** Backups left "running" by a crash/restart (no heartbeat) are failed or retried. */
    public function recoverStale(): int
    {
        $count = 0;
        Backup::where('status', Backup::STATUS_RUNNING)->get()->each(function (Backup $backup) use (&$count) {
            if (! $this->isStale($backup)) {
                return;
            }
            $this->log->event($backup, 'backup_interrupted', 'The backup was interrupted (server restart or crash).', 'warning');
            $this->fail($backup, $backup->destination, new BackupException('The backup was interrupted (server restart or crash).', 'interrupted', true), true, $backup->stage_path);
            $count++;
        });

        return $count;
    }

    public function isStale(Backup $backup): bool
    {
        $beat = $backup->heartbeat_at ?? $backup->started_at ?? $backup->updated_at;

        return $beat === null || $beat->lt(now()->subMinutes((int) config('backup-manager.stale_after_minutes', 20)));
    }

    /* ------------------------------------------------------------ helpers */

    public function deleteBackup(Backup $backup): void
    {
        if ($backup->isActive()) {
            throw new BackupException('A running backup cannot be deleted.', 'invalid_state', false);
        }

        if ($backup->destination && $backup->isSuccessful()) {
            $this->drivers->driver($backup->destination)->delete($backup->destination, $backup);
        }

        $backup->forceFill(['status' => Backup::STATUS_DELETED])->save();
        $this->log->audit('backup_deleted', "Backup {$backup->filename} deleted.", $backup);
    }

    public function heartbeat(Backup $backup): void
    {
        // throttled: at most one UPDATE every 5 seconds
        $key = 'backup-beat-' . $backup->id;
        if (Cache::add($key, 1, 5)) {
            Backup::whereKey($backup->id)->update(['heartbeat_at' => now()]);
        }
    }

    protected function filename(BackupSetting $settings): string
    {
        $tz = in_array($settings->timezone, timezone_identifiers_list(), true) ? $settings->timezone : config('app.timezone');

        return config('backup-manager.filename_prefix', 'inventory_backup') . '_' . now($tz)->format('Y-m-d_H-i-s') . '.sql.gz';
    }

    protected function stageDirectory(Backup $backup): string
    {
        $dir = rtrim(config('backup-manager.staging_path'), '/\\') . DIRECTORY_SEPARATOR . $backup->uuid;

        if (! File::isDirectory($dir) && ! @mkdir($dir, 0750, true) && ! File::isDirectory($dir)) {
            throw new BackupException('Cannot create the staging directory (permission denied).', 'permission_denied', false);
        }
        $backup->forceFill(['stage_path' => $dir])->save();

        return $dir;
    }

    protected function cleanStage(?string $stage): void
    {
        if ($stage && File::isDirectory($stage)) {
            File::deleteDirectory($stage);
        }
    }

    protected function assertStagingSpace(int $bytes): void
    {
        $dir = config('backup-manager.staging_path');
        if (! File::isDirectory($dir)) {
            @mkdir($dir, 0750, true);
        }
        $free = @disk_free_space($dir);
        if ($free !== false && $free < $bytes) {
            throw new InsufficientStorageException(sprintf('Insufficient disk space for staging the backup: %s free, about %s required.', Backup::formatBytes((int) $free), Backup::formatBytes($bytes)));
        }
    }

    protected function warnIfLowStorage(BackupDestination $destination): void
    {
        try {
            $report = $this->storage->report($destination);
            if ($report['low']) {
                $this->log->event(null, 'low_storage', "Low storage on {$destination->name}.", 'warning', ['destination_id' => $destination->id]);
                if (Cache::add('backup-low-storage-' . $destination->id, 1, 86400)) {
                    $this->notifier->notify('low_storage', 'Backup storage is low', "Free space on {$destination->name} is below " . config('backup-manager.low_storage_percent') . '%.');
                }
            }
        } catch (\Throwable) {
        }
    }
}
