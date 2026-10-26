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

/** Google Drive destination. Credentials are stored encrypted in the destination's config. */
class GoogleDriveDriver implements BackupDestinationDriver
{
    public function __construct(protected GoogleDriveClient $client)
    {
    }

    public static function type(): string
    {
        return BackupDestination::TYPE_GOOGLE_DRIVE;
    }

    public function supportsDownload(): bool
    {
        return true; // streamed through the app, never via a public link
    }

    /** Find or create the backup folder (created by this app, so drive.file scope can see it). */
    public function ensureFolder(BackupDestination $destination, string $name): void
    {
        $name = trim($name) ?: 'Inventory Backups';

        $folder = $this->client->findFolder($destination, $name) ?? $this->client->createFolder($destination, $name);

        $destination->setConf(['folder_id' => $folder['id'], 'folder_name' => $folder['name'] ?? $name]);
        $destination->save();
    }

    public function validate(BackupDestination $destination, int $requiredBytes = 0): void
    {
        if (! $destination->conf('refresh_token')) {
            throw new ConfigurationException('Google Drive is not connected.');
        }
        if (! $destination->conf('folder_id')) {
            throw new ConfigurationException('No Google Drive backup folder has been selected.');
        }

        // Exercises token refresh + API reachability in one call.
        $about = $this->client->about($destination);

        $quota = $about['storageQuota'] ?? [];
        if ($requiredBytes > 0 && isset($quota['limit'])) {
            $free = (int) $quota['limit'] - (int) ($quota['usage'] ?? 0);
            if ($free < $requiredBytes) {
                throw new InsufficientStorageException(sprintf(
                    'Insufficient Google Drive storage: %s free, about %s required.',
                    Backup::formatBytes($free),
                    Backup::formatBytes($requiredBytes)
                ));
            }
        }

        // Confirm the folder still exists and isn't trashed.
        $folder = $this->client->fileMeta($destination, $destination->conf('folder_id'));
        if (! $folder || ($folder['trashed'] ?? false)) {
            throw new DestinationUnavailableException('The Google Drive backup folder no longer exists. Please select a folder again.');
        }
    }

    public function store(BackupDestination $destination, string $localPath, string $filename, ?callable $progress = null): StoredFile
    {
        $sha256 = hash_file('sha256', $localPath);
        $size = filesize($localPath);
        $folderId = $destination->conf('folder_id');

        // Duplicate avoidance: identical content already uploaded => reuse it.
        if ($existing = $this->client->findBySha256($destination, $folderId, $sha256)) {
            return new StoredFile($existing['name'] ?? $filename, $existing['id'], (int) ($existing['size'] ?? $size));
        }

        $uploaded = $this->client->upload($destination, $localPath, $filename, $folderId, ['sha256' => $sha256], $progress);

        // Don't trust the upload call alone: re-read the file and compare.
        $meta = $this->client->fileMeta($destination, $uploaded['id']);

        $remoteSize = (int) ($meta['size'] ?? 0);
        $remoteMd5 = $meta['md5Checksum'] ?? null;

        if (! $meta || $remoteSize !== $size || ($remoteMd5 !== null && $remoteMd5 !== md5_file($localPath))) {
            try {
                $this->client->delete($destination, $uploaded['id']);
            } catch (\Throwable) {
                // ignore: surfaced by the primary failure below
            }
            throw new BackupException('Google Drive reported a different size/checksum for the uploaded file; the upload was discarded.', 'upload_mismatch', true);
        }

        return new StoredFile($filename, $uploaded['id'], $size);
    }

    public function verifyStored(BackupDestination $destination, Backup $backup): bool
    {
        if (! $backup->external_id) {
            return false;
        }

        $meta = $this->client->fileMeta($destination, $backup->external_id);

        return $meta
            && ! ($meta['trashed'] ?? false)
            && (int) ($meta['size'] ?? -1) === (int) $backup->size_bytes
            && ($meta['appProperties']['sha256'] ?? null) === $backup->checksum_sha256;
    }

    public function readStream(BackupDestination $destination, Backup $backup)
    {
        if (! $backup->external_id) {
            throw new BackupException('This backup has no Google Drive file ID.', 'missing_external_id', false);
        }

        return $this->client->stream($destination, $backup->external_id);
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
        if ($backup->external_id) {
            $this->client->delete($destination, $backup->external_id);
        }
    }

    public function storageInfo(BackupDestination $destination): array
    {
        try {
            $quota = $this->client->about($destination)['storageQuota'] ?? [];
        } catch (\Throwable) {
            return ['total' => null, 'used' => null, 'free' => null];
        }

        $total = isset($quota['limit']) ? (int) $quota['limit'] : null;
        $used = isset($quota['usage']) ? (int) $quota['usage'] : null;

        return ['total' => $total, 'used' => $used, 'free' => ($total !== null && $used !== null) ? max(0, $total - $used) : null];
    }
}
