<?php

namespace App\Services\Backup\Contracts;

use App\Models\Backup;
use App\Models\BackupDestination;
use App\Services\Backup\Support\StoredFile;

/**
 * A backup destination driver. Implement this interface (and register the class
 * in BackupDestinationManager) to add S3, Dropbox, OneDrive, NAS, SFTP, etc.
 * without touching the core backup logic.
 */
interface BackupDestinationDriver
{
    /** Stable identifier persisted in backup_destinations.type */
    public static function type(): string;

    /**
     * Check that the destination is reachable and writable.
     * Must throw a BackupException subclass with a user-friendly message on failure.
     *
     * @param int $requiredBytes minimum free space needed (0 = don't check)
     */
    public function validate(BackupDestination $destination, int $requiredBytes = 0): void;

    /**
     * Store a finished local file. Must only return once the destination has
     * confirmed that the file is stored completely. $progress is called with
     * (bytesSent, totalBytes) so callers can emit heartbeats.
     */
    public function store(BackupDestination $destination, string $localPath, string $filename, ?callable $progress = null): StoredFile;

    /** Verify that the stored copy exists and matches the expected size/checksum. */
    public function verifyStored(BackupDestination $destination, Backup $backup): bool;

    /** @return resource a readable stream of the stored file */
    public function readStream(BackupDestination $destination, Backup $backup);

    /** Download the stored file to a local path (used for restore). */
    public function download(BackupDestination $destination, Backup $backup, string $toLocalPath): void;

    /** Delete the stored file. Missing files are treated as already deleted. */
    public function delete(BackupDestination $destination, Backup $backup): void;

    /**
     * Storage figures in bytes: ['total' => ?int, 'used' => ?int, 'free' => ?int].
     * Unknown values are null.
     */
    public function storageInfo(BackupDestination $destination): array;

    /** Whether the application can serve the file to an authorised admin. */
    public function supportsDownload(): bool;
}
