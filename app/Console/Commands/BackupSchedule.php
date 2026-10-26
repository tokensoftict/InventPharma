<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Models\BackupSetting;
use App\Services\Backup\BackupLogger;
use App\Services\Backup\BackupService;
use App\Services\Backup\ScheduleCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Runs every minute from the application scheduler (routes/console.php).
 *
 * Safe to run any number of times / from several servers: a slot's deterministic key is
 * covered by a unique index, so at most one backup is ever created per slot.
 * It also heals problems: interrupted "running" backups and lost queue jobs.
 */
class BackupSchedule extends Command
{
    protected $signature = 'backup:schedule';

    protected $description = 'Dispatch scheduled database backups that are due and recover interrupted ones';

    public function handle(BackupService $service, ScheduleCalculator $calculator, BackupLogger $log): int
    {
        try {
            $service->recoverStale();
            $this->redispatchLostJobs($service);

            $settings = BackupSetting::current();
            $slot = $calculator->dueSlot($settings);

            if (! $slot) {
                return self::SUCCESS;
            }

            $destination = $settings->defaultDestination;
            if (! $destination || ! $destination->enabled) {
                if (Cache::add('backup-no-destination-' . $slot->timestamp, 1, 86400)) {
                    $log->event(null, 'schedule_skipped', 'A scheduled backup was due but no enabled default destination is configured.', 'warning');
                }

                return self::SUCCESS;
            }

            [$backup, $created] = $service->createPending($destination, Backup::TRIGGER_SCHEDULED, null, $calculator->slotKey($settings, $slot));

            if ($created) {
                $service->dispatch($backup);
                $this->info("Scheduled backup #{$backup->id} queued.");
            }
        } catch (\Throwable $e) {
            // The scheduler tick must never throw into the host's other scheduled jobs.
            $log->event(null, 'schedule_error', mb_substr($e->getMessage(), 0, 300), 'error');
            $this->error('Backup scheduler error: ' . $e->getMessage());
        }

        return self::SUCCESS;
    }

    /** A pending backup nobody picked up (queue worker down / job lost) is re-dispatched; the run itself is idempotent. */
    protected function redispatchLostJobs(BackupService $service): void
    {
        Backup::where('status', Backup::STATUS_PENDING)
            ->where(fn ($q) => $q->whereNull('next_retry_at')->orWhere('next_retry_at', '<', now()->subMinutes(5)))
            ->where('updated_at', '<', now()->subMinutes(15))
            ->get()
            ->each(function (Backup $backup) use ($service) {
                if (Cache::add('backup-redispatch-' . $backup->id, 1, 900)) {
                    $service->dispatch($backup);
                }
            });
    }
}
