<?php

namespace App\Services\Backup;

use App\Models\Backup;
use App\Models\BackupDestination;

/**
 * Per-destination retention.
 *
 * A backup is deleted only when NO configured rule still wants to keep it:
 *   - "keep last N"    keeps the N newest successful backups
 *   - "keep for D days" keeps backups newer than D days
 * With both set a backup survives if either rule keeps it. With neither set nothing
 * is ever deleted. The newest successful backup is never deleted, so a failed or
 * overly aggressive policy can never leave you with zero known-good backups.
 */
class RetentionService
{
    public function __construct(
        protected BackupDestinationManager $drivers,
        protected BackupLogger $log,
        protected BackupNotifier $notifier
    ) {
    }

    /** Backups that this policy WOULD delete (no side effects). @return \Illuminate\Support\Collection<int,Backup> */
    public function candidates(BackupDestination $destination, ?\DateTimeInterface $now = null)
    {
        $keepLast = $destination->retention_keep_last;
        $keepDays = $destination->retention_keep_days;

        if (! $keepLast && ! $keepDays) {
            return collect();
        }

        $now = $now ? \Carbon\Carbon::instance($now) : now();

        $backups = Backup::query()
            ->where('destination_id', $destination->id)
            ->where('status', Backup::STATUS_SUCCESSFUL)
            ->orderByDesc('created_at')->orderByDesc('id')
            ->get();

        return $backups->filter(function (Backup $backup, int $index) use ($keepLast, $keepDays, $now) {
            if ($index === 0) {
                return false; // newest known-good backup is untouchable
            }
            $keptByCount = $keepLast && $index < $keepLast;
            $keptByAge = $keepDays && $backup->created_at->greaterThan($now->copy()->subDays($keepDays));

            return ! $keptByCount && ! $keptByAge;
        })->values();
    }

    public function apply(BackupDestination $destination): int
    {
        $deleted = 0;
        $driver = $this->drivers->driver($destination);

        foreach ($this->candidates($destination) as $backup) {
            try {
                $driver->delete($destination, $backup);
                $backup->forceFill(['status' => Backup::STATUS_DELETED])->save();
                $this->log->event($backup, 'backup_deleted', "Deleted by retention policy: {$backup->filename}");
                $deleted++;
            } catch (\Throwable $e) {
                // keep the record as successful; it will be retried on the next cleanup
                $this->log->event($backup, 'retention_delete_failed', 'Could not delete: ' . $e->getMessage(), 'warning');
            }
        }

        if ($deleted > 0) {
            $this->log->event(null, 'retention_cleanup', "Retention removed {$deleted} backup(s) from {$destination->name}.", 'info', ['destination_id' => $destination->id]);
            $this->notifier->notify('retention_cleanup', 'Backup retention cleanup', "Retention removed {$deleted} old backup(s) from {$destination->name}.");
        }

        return $deleted;
    }
}
