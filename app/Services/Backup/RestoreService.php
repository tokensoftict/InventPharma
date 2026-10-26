<?php

namespace App\Services\Backup;

use App\Models\Backup;
use App\Models\BackupDestination;
use App\Models\BackupSetting;
use App\Services\Backup\Dump\DatabaseDumpService;
use App\Services\Backup\Dump\SqlRestoreExecutor;
use App\Services\Backup\Exceptions\BackupException;
use App\Services\Backup\Support\BackupEncryptor;
use App\Services\Backup\Support\BackupVerifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Restores a backup over the live database.
 *
 * Order of operations (each step aborts the restore BEFORE the database is touched,
 * except the last two):
 *   1. validate the backup is stored + verified
 *   2. take a fresh "safety" backup of the CURRENT database (mandatory)
 *   3. fetch the file, check its SHA-256, decrypt, verify the dump
 *   4. execute the dump
 *   5. verify the restored database against the backup's manifest
 *
 * Tables owned by the Backup Manager itself (and volatile queue/cache/session tables)
 * are never in a dump, so a restore cannot roll back backup history, settings or the
 * queue that is running the restore.
 */
class RestoreService
{
    public const LOCK = 'backup-manager-restore';

    public function __construct(
        protected BackupDestinationManager $drivers,
        protected BackupService $backups,
        protected DatabaseDumpService $dumps,
        protected BackupEncryptor $encryptor,
        protected BackupVerifier $verifier,
        protected BackupLogger $log,
        protected BackupNotifier $notifier,
    ) {
    }

    public static function statusKey(int $backupId): string
    {
        return 'backup-restore-status-' . $backupId;
    }

    public function status(int $backupId): ?array
    {
        return Cache::get(self::statusKey($backupId));
    }

    /** @param string|null $password only needed when the backup is encrypted and differs from the stored password */
    public function restore(Backup $backup, ?int $userId = null, ?string $password = null): array
    {
        $lock = Cache::lock(self::LOCK, (int) config('backup-manager.job_timeout', 7200));
        if (! $lock->get()) {
            throw new BackupException('Another restore is already in progress.', 'already_running', false);
        }

        $temp = null;
        $safety = null;

        try {
            $this->setStatus($backup, 'running', 'Preparing restore...');
            $this->log->event($backup, 'restore_started', 'Restore started.', 'warning', ['created_by' => $userId]);

            if (! $backup->isUsable() || ! $backup->destination) {
                throw new BackupException('This backup is not available for restore (it must be successful and verified).', 'invalid_state', false);
            }
            if (Backup::where('status', Backup::STATUS_RUNNING)->where('trigger', '!=', Backup::TRIGGER_SAFETY)->exists()) {
                throw new BackupException('A backup is currently running. Try again when it has finished.', 'busy', false);
            }

            $destination = $backup->destination;
            $driver = $this->drivers->driver($destination);

            // 1+2. Can we reach the backup and decrypt it? Check BEFORE changing anything.
            $driver->validate($destination);
            $password = $this->resolvePassword($backup, $password);

            // 2. Safety backup of the current state
            $this->setStatus($backup, 'running', 'Creating safety backup of the current database...');
            $safety = $this->safetyBackup($userId);
            $this->log->event($backup, 'restore_safety_backup', "Safety backup #{$safety->id} created ({$safety->filename}).");

            // 3. Fetch + verify + decrypt
            $this->setStatus($backup, 'running', 'Downloading and verifying the backup...');
            $temp = rtrim(config('backup-manager.staging_path'), '/\\') . DIRECTORY_SEPARATOR . 'restore-' . Str::uuid();
            File::ensureDirectoryExists($temp, 0750);

            $downloaded = $temp . DIRECTORY_SEPARATOR . 'download';
            $driver->download($destination, $backup, $downloaded);

            if (hash_file('sha256', $downloaded) !== $backup->checksum_sha256) {
                throw new BackupException('The downloaded backup does not match its recorded checksum; it may be corrupted. Nothing was changed.', 'checksum_mismatch', false);
            }

            $gz = $downloaded;
            if ($backup->encrypted) {
                $gz = $temp . DIRECTORY_SEPARATOR . 'plain.sql.gz';
                $this->encryptor->decryptFile($downloaded, $gz, $password);
            }
            $this->verifier->verifyGzipDump($gz);

            // 4. Execute
            $this->setStatus($backup, 'running', 'Restoring the database. Do not close the server...');
            $pdo = $this->dumps->dedicatedConnection('backup_restore')->getPdo();
            $pdo->exec("SET SESSION time_zone = '+00:00'");
            $statements = (new SqlRestoreExecutor($pdo))->executeGzip($gz, fn () => $this->setStatus($backup, 'running', 'Restoring the database...'));

            // 5. Verify
            $verification = $this->verifyRestored($backup);
            $this->log->event($backup, 'restore_verified', 'Restored database verified.', 'info', $verification);

            $this->log->audit('backup_restored', "Backup {$backup->filename} restored.", $backup, ['safety_backup_id' => $safety->id, 'statements' => $statements]);
            $this->log->event($backup, 'restore_completed', 'Restore completed.', 'info', ['safety_backup_id' => $safety->id]);
            $this->notifier->notify('restore_completed', 'Database restore completed', "Backup {$backup->filename} was restored. A safety backup of the previous state is #{$safety->id}.");
            $this->setStatus($backup, 'completed', "Restore completed. A safety backup of the previous data is #{$safety->id}.");

            return ['safety_backup_id' => $safety->id, 'statements' => $statements] + $verification;
        } catch (\Throwable $e) {
            $message = $e instanceof BackupException ? $e->getMessage() : 'Restore failed: ' . mb_substr($e->getMessage(), 0, 300);
            if ($safety) {
                $message .= " The previous data is saved in safety backup #{$safety->id}.";
            }
            $this->log->event($backup, 'restore_failed', $message, 'error');
            $this->notifier->notify('restore_failed', 'Database restore failed', $message);
            $this->setStatus($backup, 'failed', $message);

            throw $e instanceof BackupException ? new BackupException($message, $e->errorCode, false, $e) : new BackupException($message, 'restore_failed', false, $e);
        } finally {
            if ($temp) {
                File::deleteDirectory($temp);
            }
            $lock->release();
        }
    }

    protected function safetyBackup(?int $userId): Backup
    {
        $settings = BackupSetting::current();
        $destination = $settings->defaultDestination;

        if (! $destination || ! $destination->enabled) {
            $destination = BackupDestination::where('enabled', true)->where('status', BackupDestination::STATUS_READY)->orderBy('id')->first();
        }
        if (! $destination) {
            throw new BackupException('Restore aborted: no usable backup destination is available for the mandatory safety backup. Nothing was changed.', 'no_destination', false);
        }

        [$safety] = $this->backups->createPending($destination, Backup::TRIGGER_SAFETY, $userId);
        $this->backups->run($safety, false); // synchronous, no retries: it either works or we abort
        $safety->refresh();

        if (! $safety->isSuccessful()) {
            throw new BackupException('Restore aborted: the safety backup of the current database failed (' . ($safety->error_message ?: 'unknown error') . '). Nothing was changed.', 'safety_backup_failed', false);
        }

        return $safety;
    }

    protected function resolvePassword(Backup $backup, ?string $given): ?string
    {
        if (! $backup->encrypted) {
            return null;
        }
        $password = $given ?: BackupSetting::current()->encryption_password;
        if (! $password) {
            throw new BackupException('This backup is encrypted. Enter the encryption password to restore it.', 'password_required', false);
        }

        return $password;
    }

    /** Compare table count (and row counts when the backup recorded them) with the live database. */
    protected function verifyRestored(Backup $backup): array
    {
        $meta = $backup->meta ?? [];
        $excluded = $this->dumps->excludedTables();
        $conn = DB::connection(config('backup-manager.database_connection'));

        $tables = collect($conn->select('SELECT table_name AS n FROM information_schema.tables WHERE table_schema = ? AND table_type = "BASE TABLE"', [$this->dumps->databaseName()]))
            ->pluck('n')->map(fn ($n) => (string) $n)->reject(fn ($n) => in_array($n, $excluded, true))->values();

        $result = ['tables' => $tables->count()];

        if (isset($meta['tables']) && $tables->count() < (int) $meta['tables']) {
            throw new BackupException("Restore verification failed: expected at least {$meta['tables']} tables but found {$tables->count()}.", 'verification_failed', false);
        }

        if (! empty($meta['rows']) && is_array($meta['rows'])) {
            $mismatch = [];
            foreach ($meta['rows'] as $table => $expected) {
                if (! $tables->contains($table)) {
                    $mismatch[] = "{$table} (missing)";
                    continue;
                }
                $actual = (int) $conn->table($table)->count();
                if ($actual !== (int) $expected) {
                    $mismatch[] = "{$table} ({$actual} rows, expected {$expected})";
                }
            }
            if ($mismatch) {
                throw new BackupException('Restore verification failed for: ' . implode(', ', array_slice($mismatch, 0, 5)), 'verification_failed', false);
            }
            $result['row_counts_verified'] = true;
        }

        return $result;
    }

    protected function setStatus(Backup $backup, string $state, string $message): void
    {
        Cache::put(self::statusKey($backup->id), ['state' => $state, 'message' => $message, 'at' => now()->toDateTimeString()], 86400);
    }
}
