<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Models\BackupDestination;
use App\Models\BackupSetting;
use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class BackupRun extends Command
{
    protected $signature = 'backup:run {--destination= : Destination ID (defaults to the default destination)} {--sync : Run in this process instead of the queue}';

    protected $description = 'Run a database backup now from the command line';

    public function handle(BackupService $service): int
    {
        $settings = BackupSetting::current();
        $id = $this->option('destination') ?: $settings->default_destination_id;
        $destination = $id ? BackupDestination::find($id) : null;

        if (! $destination || ! $destination->enabled) {
            $this->error('No enabled destination. Configure one in Settings > Backup Manager.');

            return self::FAILURE;
        }

        [$backup] = $service->createPending($destination, Backup::TRIGGER_MANUAL);

        if ($this->option('sync')) {
            $backup = $service->run($backup);
            $this->line("Backup #{$backup->id}: {$backup->status}" . ($backup->error_message ? " - {$backup->error_message}" : ''));

            return $backup->isSuccessful() ? self::SUCCESS : self::FAILURE;
        }

        $service->dispatch($backup);
        $this->info("Backup #{$backup->id} queued.");

        return self::SUCCESS;
    }
}
