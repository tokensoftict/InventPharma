<?php

namespace App\Services\Backup\Drivers;

use App\Models\Backup;
use App\Models\BackupDestination;
use App\Services\Backup\Contracts\BackupDestinationDriver;
use App\Services\Backup\Exceptions\BackupException;
use App\Services\Backup\Exceptions\ConfigurationException;
use App\Services\Backup\Exceptions\DestinationUnavailableException;
use App\Services\Backup\Support\StoredFile;
use Aws\Exception\AwsException;
use Aws\Exception\CredentialsException;
use Aws\Exception\MultipartUploadException;
use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;
use Illuminate\Support\Str;

/**
 * S3-compatible object storage destination (Contabo Object Storage, AWS S3, MinIO, ...).
 *
 * Credentials are NOT stored in the database: they are read from the Laravel filesystem
 * disk named in config('backup-manager.s3.disk') (default "contabo"), i.e. from .env.
 * The destination only stores the folder (key prefix) inside the bucket.
 *
 * Safety design:
 *  - Backups live under "<prefix>/" in the bucket, separate from the application's pictures.
 *  - Objects are never overwritten (a unique name is chosen if the key already exists).
 *  - The SHA-256 is stored as object metadata and size is re-read with HEAD after upload;
 *    a mismatch discards the object and fails the run.
 */
class S3Driver implements BackupDestinationDriver
{
    public const DEFAULT_PREFIX = 'inventory-backups';

    public static function type(): string
    {
        return BackupDestination::TYPE_S3;
    }

    public function supportsDownload(): bool
    {
        return true; // streamed through the app; the bucket is never exposed publicly
    }

    /** Whether the .env/disk credentials needed for this destination are present. */
    public static function credentialsConfigured(): bool
    {
        $c = static::diskConfig();

        return filled($c['key'] ?? null) && filled($c['secret'] ?? null)
            && filled($c['bucket'] ?? null) && filled($c['endpoint'] ?? null);
    }

    public static function bucketName(): ?string
    {
        return static::diskConfig()['bucket'] ?? null;
    }

    /** Create the folder (key prefix) in the bucket. Called when the destination is saved. */
    public function initialize(BackupDestination $destination): void
    {
        $prefix = $this->prefix($destination);

        try {
            $this->client()->putObject([
                'Bucket'        => $this->bucket(),
                'Key'           => $prefix . '/',
                'Body'          => '',
                'ContentLength' => 0,
            ]);
        } catch (\Throwable $e) {
            throw $this->translate($e, 'create the backup folder');
        }
    }

    public function validate(BackupDestination $destination, int $requiredBytes = 0): void
    {
        $prefix = $this->prefix($destination);
        $client = $this->client();

        try {
            $client->headBucket(['Bucket' => $this->bucket()]);

            // Prove we can really write and delete inside the backup folder.
            $probe = $prefix . '/.write-test-' . Str::lower(Str::random(8));
            $client->putObject(['Bucket' => $this->bucket(), 'Key' => $probe, 'Body' => 'ok']);
            $client->deleteObject(['Bucket' => $this->bucket(), 'Key' => $probe]);
        } catch (\Throwable $e) {
            throw $this->translate($e, 'reach the bucket');
        }
        // Object storage has no per-bucket quota API: free space is not checked.
    }

    public function store(BackupDestination $destination, string $localPath, string $filename, ?callable $progress = null): StoredFile
    {
        $client = $this->client();
        $size = filesize($localPath);
        $sha256 = hash_file('sha256', $localPath);
        $prefix = $this->prefix($destination);

        try {
            $key = $this->uniqueKey($client, $prefix, $filename);
        } catch (\Throwable $e) {
            throw $this->translate($e, 'check the backup folder');
        }

        $source = @fopen($localPath, 'rb');
        if (! $source) {
            throw new BackupException('Could not read the local backup file for upload.', 'read_failed', false);
        }

        try {
            $uploader = new ObjectUploader($client, $this->bucket(), $key, $source, 'private', [
                // multipart: metadata goes on CreateMultipartUpload
                'before_initiate' => function ($command) use ($sha256) {
                    $command['Metadata'] = ['sha256' => $sha256];
                },
                // single PUT: metadata goes on PutObject; also a heartbeat per part/request
                'before_upload'   => function ($command) use ($sha256, $progress, $size) {
                    if ($command->getName() === 'PutObject') {
                        $command['Metadata'] = ['sha256' => $sha256];
                    }
                    $progress && $progress(0, $size);
                },
                'part_size'       => 16 * 1024 * 1024,
                'concurrency'     => 1,
                'mup_threshold'   => 32 * 1024 * 1024,
            ]);

            $uploader->upload();
            $progress && $progress($size, $size);
        } catch (\Throwable $e) {
            throw $this->translate($e, 'upload the backup');
        } finally {
            is_resource($source) && fclose($source);
        }

        // Don't trust the upload call alone: re-read the object and compare.
        try {
            $head = $client->headObject(['Bucket' => $this->bucket(), 'Key' => $key]);
        } catch (\Throwable $e) {
            throw $this->translate($e, 'verify the uploaded backup');
        }

        $remoteSize = (int) ($head['ContentLength'] ?? -1);
        if ($remoteSize !== $size) {
            $this->safeDelete($client, $key);
            throw new BackupException("The storage reported {$remoteSize} bytes for the uploaded file (expected {$size}); the upload was discarded.", 'upload_mismatch', true);
        }

        return new StoredFile($key, $key, $size);
    }

    public function verifyStored(BackupDestination $destination, Backup $backup): bool
    {
        $key = $this->keyFor($destination, $backup);

        try {
            $head = $this->client()->headObject(['Bucket' => $this->bucket(), 'Key' => $key]);
        } catch (\Throwable) {
            return false;
        }

        return (int) ($head['ContentLength'] ?? -1) === (int) $backup->size_bytes
            && (($head['Metadata']['sha256'] ?? null) === $backup->checksum_sha256);
    }

    public function readStream(BackupDestination $destination, Backup $backup)
    {
        try {
            $result = $this->client()->getObject([
                'Bucket' => $this->bucket(),
                'Key'    => $this->keyFor($destination, $backup),
                '@http'  => ['stream' => true],
            ]);
        } catch (\Throwable $e) {
            throw $this->translate($e, 'read the backup');
        }

        $stream = $result['Body']->detach();
        if (! is_resource($stream)) {
            throw new BackupException('Could not open the stored backup for reading.', 'read_failed', true);
        }

        return $stream;
    }

    public function download(BackupDestination $destination, Backup $backup, string $toLocalPath): void
    {
        try {
            $this->client()->getObject([
                'Bucket'  => $this->bucket(),
                'Key'     => $this->keyFor($destination, $backup),
                'SaveAs'  => $toLocalPath,
            ]);
        } catch (\Throwable $e) {
            @unlink($toLocalPath);
            throw $this->translate($e, 'download the backup');
        }
    }

    public function delete(BackupDestination $destination, Backup $backup): void
    {
        $key = $this->keyFor($destination, $backup);

        try {
            // S3 deleteObject is idempotent: a missing object is not an error.
            $this->client()->deleteObject(['Bucket' => $this->bucket(), 'Key' => $key]);
        } catch (\Throwable $e) {
            throw $this->translate($e, 'delete the backup');
        }
    }

    public function storageInfo(BackupDestination $destination): array
    {
        return ['total' => null, 'used' => null, 'free' => null]; // unmetered object storage
    }

    /* ------------------------------------------------------------------ helpers */

    protected static function diskConfig(): array
    {
        return (array) config('filesystems.disks.' . config('backup-manager.s3.disk', 'contabo'), []);
    }

    protected function bucket(): string
    {
        return (string) (static::diskConfig()['bucket'] ?? '');
    }

    /** Build a client from the disk config with settings that S3-compatible providers accept. */
    protected function client(): S3Client
    {
        if (! static::credentialsConfigured()) {
            throw new ConfigurationException('S3 storage credentials are missing. Set the CONTABO_* values in the .env file (access key, secret, region, bucket, endpoint).');
        }

        $c = static::diskConfig();

        return new S3Client([
            'version'                     => 'latest',
            'region'                      => $c['region'] ?: 'us-east-1',
            'endpoint'                    => $c['endpoint'],
            'use_path_style_endpoint'     => (bool) ($c['use_path_style_endpoint'] ?? true),
            'credentials'                 => ['key' => $c['key'], 'secret' => $c['secret']],
            // Many S3-compatible stores (incl. Contabo) reject the newer default CRC checksums.
            'request_checksum_calculation' => 'when_required',
            'response_checksum_validation' => 'when_required',
            'http'                        => ['connect_timeout' => 15, 'timeout' => 600],
        ]);
    }

    /** Sanitised folder inside the bucket, without leading/trailing slashes. */
    protected function prefix(BackupDestination $destination): string
    {
        $prefix = trim((string) $destination->conf('prefix', config('backup-manager.s3.prefix', self::DEFAULT_PREFIX)), "/ \t");

        if ($prefix === '' || str_contains($prefix, '..') || ! preg_match('~^[A-Za-z0-9._\-/]+$~', $prefix)) {
            throw new ConfigurationException('The backup folder name may only contain letters, numbers, dots, dashes, underscores and slashes.');
        }

        return $prefix;
    }

    /** Resolve a backup's object key and guarantee it lives inside the destination folder. */
    protected function keyFor(BackupDestination $destination, Backup $backup): string
    {
        $key = (string) ($backup->path ?: $backup->external_id);

        if ($key === '' || str_contains($key, '..') || ! str_starts_with($key, $this->prefix($destination) . '/')) {
            throw new BackupException('Backup file is outside the configured storage folder.', 'invalid_path', false);
        }

        return $key;
    }

    protected function uniqueKey(S3Client $client, string $prefix, string $filename): string
    {
        $key = $prefix . '/' . $filename;

        if ($client->doesObjectExistV2($this->bucket(), $key)) {
            // Never overwrite a known-good file.
            $key = $prefix . '/' . pathinfo($filename, PATHINFO_FILENAME) . '_' . Str::lower(Str::random(6)) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
        }

        return $key;
    }

    protected function safeDelete(S3Client $client, string $key): void
    {
        try {
            $client->deleteObject(['Bucket' => $this->bucket(), 'Key' => $key]);
        } catch (\Throwable) {
            // surfaced by the primary failure
        }
    }

    /** Map SDK/network errors to the Backup Manager's user-friendly exceptions. */
    protected function translate(\Throwable $e, string $action): BackupException
    {
        if ($e instanceof BackupException) {
            return $e;
        }
        if ($e instanceof MultipartUploadException && $e->getPrevious() instanceof \Throwable) {
            $e = $e->getPrevious();
        }

        if ($e instanceof CredentialsException) {
            return new BackupException('S3 credentials are invalid or missing.', 'permission_denied', false, $e);
        }

        if ($e instanceof AwsException) {
            $code = (string) $e->getAwsErrorCode();
            $status = (int) $e->getStatusCode();

            if (in_array($code, ['InvalidAccessKeyId', 'SignatureDoesNotMatch', 'AccessDenied', 'AllAccessDisabled'], true) || in_array($status, [401, 403], true)) {
                return new BackupException("S3 refused access while trying to {$action}: check the access key, secret and bucket permissions.", 'permission_denied', false, $e);
            }
            if ($code === 'NoSuchBucket' || $status === 404) {
                return new ConfigurationException("The S3 bucket was not found while trying to {$action}. Check CONTABO_BUCKET and CONTABO_ENDPOINT.", $e);
            }
            if ($e->getResponse() === null) { // no HTTP response => connection problem
                return new DestinationUnavailableException("Could not connect to the S3 endpoint to {$action}.", $e);
            }

            return new BackupException("S3 error while trying to {$action}: " . ($e->getAwsErrorMessage() ?: $e->getMessage()), 's3_error', true, $e);
        }

        return new DestinationUnavailableException("Could not {$action}: " . Str::limit($e->getMessage(), 200), $e);
    }
}
