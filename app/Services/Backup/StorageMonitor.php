<?php

namespace App\Services\Backup;

use App\Models\Backup;
use App\Models\BackupDestination;

/** Storage figures for the dashboard and low-storage warnings. */
class StorageMonitor
{
    public function __construct(protected BackupDestinationManager $drivers)
    {
    }

    /** @return array{total:?int,used:?int,free:?int,backups:int,backup_count:int,low:bool} */
    public function report(BackupDestination $destination): array
    {
        $info = ['total' => null, 'used' => null, 'free' => null];

        try {
            $info = $this->drivers->driver($destination)->storageInfo($destination);
        } catch (\Throwable) {
            // unreachable destination: leave figures unknown
        }

        $mine = Backup::query()->where('destination_id', $destination->id)->where('status', Backup::STATUS_SUCCESSFUL);

        return $info + [
            'backups'      => (int) (clone $mine)->sum('size_bytes'),
            'backup_count' => (int) (clone $mine)->count(),
            'low'          => $this->isLow($info),
        ];
    }

    public function isLow(array $info): bool
    {
        if (! isset($info['total'], $info['free']) || ! $info['total']) {
            return false;
        }

        return ($info['free'] / $info['total']) * 100 < (float) config('backup-manager.low_storage_percent', 10);
    }

    /** Bytes we expect a new backup to need (before the safety factor). */
    public function estimatedBackupBytes(?int $databaseEstimate = null): int
    {
        $last = Backup::query()->where('status', Backup::STATUS_SUCCESSFUL)->latest('id')->value('size_bytes');

        $estimate = $last ?: (int) (($databaseEstimate ?? 0) * 0.4);

        return max($estimate, (int) config('backup-manager.min_free_space_mb', 100) * 1048576);
    }

    public function requiredBytes(?int $databaseEstimate = null): int
    {
        return (int) ceil($this->estimatedBackupBytes($databaseEstimate) * (float) config('backup-manager.space_safety_factor', 1.25));
    }
}
