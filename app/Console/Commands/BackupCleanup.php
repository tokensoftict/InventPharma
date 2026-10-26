<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Models\BackupDestination;
use App\Services\Backup\RetentionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupCleanup extends Command
{
    protected $signature = 'backup:cleanup';

    protected $description = 'Apply backup retention policies and remove abandoned staging files';

    public function handle(RetentionService $retention): int
    {
        $deleted = 0;

        BackupDestination::where('enabled', true)->get()->each(function (BackupDestination $destination) use ($retention, &$deleted) {
            try {
                $deleted += $retention->apply($destination);
            } catch (\Throwable $e) {
                $this->warn("Retention skipped for {$destination->name}: " . $e->getMessage());
            }
        });

        // Staging directories of finished/old backups (never touch one still in use).
        $root = config('backup-manager.staging_path');
        $cutoff = now()->subHours((int) config('backup-manager.staging_keep_hours', 48))->timestamp;
        $removed = 0;

        if (File::isDirectory($root)) {
            foreach (File::directories($root) as $dir) {
                if (File::lastModified($dir) > $cutoff) {
                    continue;
                }
                $uuid = basename($dir);
                if (Backup::where('uuid', $uuid)->whereIn('status', [Backup::STATUS_PENDING, Backup::STATUS_RUNNING])->exists()) {
                    continue;
                }
                File::deleteDirectory($dir);
                $removed++;
            }
        }

        $this->info("Retention removed {$deleted} backup(s); {$removed} stale staging folder(s) cleaned.");

        return self::SUCCESS;
    }
}
