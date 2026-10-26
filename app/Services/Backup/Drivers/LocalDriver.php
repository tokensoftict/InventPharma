<?php

namespace App\Services\Backup\Drivers;

use App\Models\Backup;
use App\Models\BackupDestination;
use App\Services\Backup\Contracts\BackupDestinationDriver;
use App\Services\Backup\Exceptions\BackupException;
use App\Services\Backup\Exceptions\ConfigurationException;
use App\Services\Backup\Exceptions\DestinationUnavailableException;
use App\Services\Backup\Exceptions\InsufficientStorageException;
use App\Services\Backup\Support\StoredFile;
use Illuminate\Support\Str;

/**
 * Local filesystem destination: server disk, external HDD/SSD, flash drive, mounted NAS share.
 *
 * Safety design:
 *  - A hidden marker file is written when the destination is initialised. Every later run
 *    requires that marker. If a removable drive is unplugged (or a different drive is
 *    mounted at the same path) the marker is missing and the run fails with
 *    "Backup destination unavailable." instead of silently writing to the system disk.
 *  - Files are written to "<name>.part" and renamed only after the size is confirmed,
 *    so a partial file is never mistaken for a backup and existing files are never overwritten.
 */
class LocalDriver implements BackupDestinationDriver
{
    public const MARKER_FILE = '.inventory-backup-destination';

    public static function type(): string
    {
        return BackupDestination::TYPE_LOCAL;
    }

    public function supportsDownload(): bool
    {
        return true;
    }

    /**
     * One-time (or re-test) initialisation, called from "Test Destination" / saving settings.
     * Unlike validate() this MAY create the directory and writes the marker.
     */
    public function initialize(BackupDestination $destination): void
    {
        $path = $this->configuredPath($destination);

        if (! is_dir($path)) {
            if (! @mkdir($path, 0750, true) && ! is_dir($path)) {
                throw new DestinationUnavailableException("Cannot create directory [{$path}].");
            }
        }

        $marker = $destination->conf('marker') ?: (string) Str::uuid();
        $markerPath = $path . DIRECTORY_SEPARATOR . self::MARKER_FILE;

        if (! is_file($markerPath) && @file_put_contents($markerPath, $marker) === false) {
            throw new BackupException("Permission denied writing to [{$path}].", 'permission_denied', false);
        }

        // An existing marker belongs to whatever drive is mounted now: adopt it.
        $destination->setConf(['marker' => trim((string) @file_get_contents($markerPath))]);
        $destination->save();
    }

    public function validate(BackupDestination $destination, int $requiredBytes = 0): void
    {
        $path = $this->configuredPath($destination);

        if (! is_dir($path)) {
            throw new DestinationUnavailableException("Directory [{$path}] was not found. Is the drive connected?");
        }

        $expected = $destination->conf('marker');
        if (! $expected) {
            throw new ConfigurationException('This destination has not been initialised. Use "Test Destination" first.');
        }

        $markerPath = $path . DIRECTORY_SEPARATOR . self::MARKER_FILE;
        if (! is_file($markerPath) || trim((string) @file_get_contents($markerPath)) !== $expected) {
            throw new DestinationUnavailableException('The configured drive/folder is not the one that was set up (drive disconnected or replaced).');
        }

        if (! is_writable($path)) {
            throw new BackupException("Permission denied: the application cannot write to [{$path}].", 'permission_denied', false);
        }

        $probe = $path . DIRECTORY_SEPARATOR . '.write-test-' . Str::random(8);
        if (@file_put_contents($probe, 'ok') === false) {
            throw new BackupException("Permission denied: cannot create files in [{$path}].", 'permission_denied', false);
        }
        @unlink($probe);

        $free = @disk_free_space($path);
        if ($free !== false && $requiredBytes > 0 && $free < $requiredBytes) {
            throw new InsufficientStorageException(sprintf(
                'Insufficient storage on destination: %s free, about %s required.',
                Backup::formatBytes((int) $free),
                Backup::formatBytes($requiredBytes)
            ));
        }
    }

    public function store(BackupDestination $destination, string $localPath, string $filename, ?callable $progress = null): StoredFile
    {
        $dir = $this->configuredPath($destination);
        $target = $dir . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($target)) {
            // Never overwrite a known-good file.
            $filename = pathinfo($filename, PATHINFO_FILENAME) . '_' . Str::lower(Str::random(6)) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
            $target = $dir . DIRECTORY_SEPARATOR . $filename;
        }

        $part = $target . '.part';
        $total = filesize($localPath);

        $in = @fopen($localPath, 'rb');
        $out = @fopen($part, 'wb');
        if (! $in || ! $out) {
            $in && fclose($in);
            $out && fclose($out);
            @unlink($part);
            throw new BackupException('Could not open files for copying to the destination (permission denied?).', 'permission_denied', false);
        }

        $sent = 0;
        try {
            while (! feof($in)) {
                $chunk = fread($in, 1024 * 1024);
                if ($chunk === false || fwrite($out, $chunk) === false) {
                    throw new DestinationUnavailableException('Write failed (disk full or drive removed).');
                }
                $sent += strlen($chunk);
                $progress && $progress($sent, $total);
            }
            fflush($out);
        } catch (\Throwable $e) {
            fclose($in);
            fclose($out);
            @unlink($part);
            throw $e;
        }
        fclose($in);
        fclose($out);

        if (filesize($part) !== $total) {
            @unlink($part);
            throw new DestinationUnavailableException('Stored file size does not match; the copy was incomplete.');
        }

        if (! @rename($part, $target)) {
            @unlink($part);
            throw new DestinationUnavailableException('Could not finalise the stored file.');
        }

        return new StoredFile($target, null, $total);
    }

    public function verifyStored(BackupDestination $destination, Backup $backup): bool
    {
        $file = $this->safeFilePath($destination, $backup);

        if (! is_file($file) || filesize($file) !== (int) $backup->size_bytes) {
            return false;
        }

        return hash_file('sha256', $file) === $backup->checksum_sha256;
    }

    public function readStream(BackupDestination $destination, Backup $backup)
    {
        $stream = @fopen($this->safeFilePath($destination, $backup), 'rb');
        if (! $stream) {
            throw new DestinationUnavailableException('The backup file could not be opened.');
        }

        return $stream;
    }

    public function download(BackupDestination $destination, Backup $backup, string $toLocalPath): void
    {
        $in = $this->readStream($destination, $backup);
        $out = fopen($toLocalPath, 'wb');
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);
    }

    public function delete(BackupDestination $destination, Backup $backup): void
    {
        $file = $this->safeFilePath($destination, $backup);

        if (is_file($file) && ! @unlink($file)) {
            throw new BackupException("Could not delete [{$backup->filename}] (permission denied or drive read-only).", 'delete_failed', false);
        }
    }

    public function storageInfo(BackupDestination $destination): array
    {
        $path = (string) $destination->conf('path');
        if ($path === '' || ! is_dir($path)) {
            return ['total' => null, 'used' => null, 'free' => null];
        }

        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        return [
            'total' => $total === false ? null : (int) $total,
            'free'  => $free === false ? null : (int) $free,
            'used'  => ($total === false || $free === false) ? null : (int) ($total - $free),
        ];
    }

    protected function configuredPath(BackupDestination $destination): string
    {
        $path = rtrim(trim((string) $destination->conf('path')), "/\\");

        if ($path === '') {
            throw new ConfigurationException('No backup directory has been configured.');
        }

        // absolute POSIX, Windows drive, or UNC path only
        if (! preg_match('~^(/|[A-Za-z]:[\\\\/]|\\\\\\\\)~', $path) || str_contains($path, '..')) {
            throw new ConfigurationException('The backup directory must be an absolute path without "..".');
        }

        // Never store backups where the web server could serve them.
        $public = realpath(public_path());
        $real = realpath($path) ?: $path;
        if ($public && str_starts_with(str_replace('\\', '/', $real), str_replace('\\', '/', $public))) {
            throw new ConfigurationException('The backup directory must not be inside the public web directory.');
        }

        return $path;
    }

    /** Resolve a backup's file and guarantee it lives inside the destination directory. */
    protected function safeFilePath(BackupDestination $destination, Backup $backup): string
    {
        $dir = $this->configuredPath($destination);
        $file = (string) $backup->path;

        if ($file === '' || dirname($file) !== $dir && realpath(dirname($file)) !== realpath($dir)) {
            throw new BackupException('Backup file is outside the configured destination.', 'invalid_path', false);
        }

        return $file;
    }
}
