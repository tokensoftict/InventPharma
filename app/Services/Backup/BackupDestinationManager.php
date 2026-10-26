<?php

namespace App\Services\Backup;

use App\Models\BackupDestination;
use App\Services\Backup\Contracts\BackupDestinationDriver;
use App\Services\Backup\Drivers\GoogleDriveDriver;
use App\Services\Backup\Drivers\LocalDriver;
use App\Services\Backup\Exceptions\ConfigurationException;

/**
 * Registry/factory for destination drivers.
 *
 *   BackupDestinationManager::extend('s3', S3Driver::class);
 */
class BackupDestinationManager
{
    /** @var array<string, class-string<BackupDestinationDriver>> */
    protected static array $drivers = [
        BackupDestination::TYPE_LOCAL        => LocalDriver::class,
        BackupDestination::TYPE_GOOGLE_DRIVE => GoogleDriveDriver::class,
    ];

    public static function extend(string $type, string $driverClass): void
    {
        static::$drivers[$type] = $driverClass;
    }

    /** @return array<string, class-string<BackupDestinationDriver>> */
    public static function types(): array
    {
        return static::$drivers;
    }

    public function driver(BackupDestination|string $destinationOrType): BackupDestinationDriver
    {
        $type = $destinationOrType instanceof BackupDestination ? $destinationOrType->type : $destinationOrType;

        if (! isset(static::$drivers[$type])) {
            throw new ConfigurationException("Unsupported backup destination type [{$type}].");
        }

        return app(static::$drivers[$type]);
    }
}
