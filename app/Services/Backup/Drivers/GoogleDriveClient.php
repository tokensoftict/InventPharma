<?php

namespace App\Services\Backup\Drivers;

use App\Models\BackupDestination;
use App\Models\BackupSetting;
use App\Services\Backup\Exceptions\AuthenticationExpiredException;
use App\Services\Backup\Exceptions\BackupException;
use App\Services\Backup\Exceptions\ConfigurationException;
use App\Services\Backup\Exceptions\DestinationUnavailableException;
use App\Services\Backup\Exceptions\InsufficientStorageException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin Google OAuth2 + Drive v3 REST client.
 * All tokens are read from / written to the destination's encrypted config.
 */
class GoogleDriveClient
{
    public const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    public const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    public const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';
    public const API = 'https://www.googleapis.com/drive/v3';
    public const UPLOAD = 'https://www.googleapis.com/upload/drive/v3';

    public function clientId(): ?string
    {
        return BackupSetting::current()->effectiveGoogleClientId();
    }

    public function clientSecret(): ?string
    {
        return BackupSetting::current()->effectiveGoogleClientSecret();
    }

    public function isConfigured(): bool
    {
        return filled($this->clientId()) && filled($this->clientSecret());
    }

    /* ---------------------------------------------------------------- OAuth */

    public function authUrl(string $state, string $redirectUri): string
    {
        $this->assertConfigured();

        return self::AUTH_URL . '?' . http_build_query([
            'client_id'              => $this->clientId(),
            'redirect_uri'           => $redirectUri,
            'response_type'          => 'code',
            'scope'                  => config('backup-manager.google.scope'),
            'access_type'            => 'offline',   // we need a refresh token
            'prompt'                 => 'consent',   // always return the refresh token
            'include_granted_scopes' => 'true',
            'state'                  => $state,
        ]);
    }

    /** @return array{access_token:string, refresh_token:?string, expires_at:int} */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $this->assertConfigured();

        $response = $this->send(fn () => Http::asForm()->timeout($this->timeout())->post(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]));

        if ($response->failed()) {
            throw new BackupException('Google rejected the authorisation code: ' . $this->errorMessage($response), 'oauth_failed', false);
        }

        return [
            'access_token'  => $response->json('access_token'),
            'refresh_token' => $response->json('refresh_token'),
            'expires_at'    => time() + (int) $response->json('expires_in', 3600),
        ];
    }

    public function revoke(?string $token): void
    {
        if (! $token) {
            return;
        }
        try {
            Http::asForm()->timeout(15)->post(self::REVOKE_URL, ['token' => $token]);
        } catch (\Throwable) {
            // Best effort: the local tokens are deleted regardless.
        }
    }

    /** Returns a valid access token, refreshing (and persisting) it when expired. */
    public function accessToken(BackupDestination $destination): string
    {
        $token = $destination->conf('access_token');
        $expires = (int) $destination->conf('expires_at', 0);

        if ($token && $expires - 60 > time()) {
            return $token;
        }

        $refresh = $destination->conf('refresh_token');
        if (! $refresh) {
            $this->flagAuthExpired($destination);
            throw new AuthenticationExpiredException();
        }

        $this->assertConfigured();

        $response = $this->send(fn () => Http::asForm()->timeout($this->timeout())->post(self::TOKEN_URL, [
            'client_id'     => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'refresh_token' => $refresh,
            'grant_type'    => 'refresh_token',
        ]));

        if ($response->failed()) {
            if (in_array($response->json('error'), ['invalid_grant', 'invalid_client', 'unauthorized_client'], true) || $response->status() === 401) {
                $this->flagAuthExpired($destination);
                throw new AuthenticationExpiredException();
            }
            throw new BackupException('Could not refresh the Google access token: ' . $this->errorMessage($response), 'oauth_refresh_failed', true);
        }

        $destination->setConf([
            'access_token' => $response->json('access_token'),
            'expires_at'   => time() + (int) $response->json('expires_in', 3600),
        ]);
        $destination->save();

        return $destination->conf('access_token');
    }

    /* ---------------------------------------------------------------- Drive */

    /** Authenticated request builder. */
    public function http(BackupDestination $destination): PendingRequest
    {
        return Http::withToken($this->accessToken($destination))->timeout($this->timeout())->withoutRedirecting();
    }

    public function request(BackupDestination $destination, string $method, string $url, array $query = [], array $json = null): Response
    {
        $response = $this->send(function () use ($destination, $method, $url, $query, $json) {
            $pending = $this->http($destination);

            return $json === null
                ? $pending->send($method, $url, ['query' => $query])
                : $pending->send($method, $url, ['query' => $query, 'json' => $json]);
        });

        $this->assertOk($destination, $response);

        return $response;
    }

    public function about(BackupDestination $destination): array
    {
        return $this->request($destination, 'GET', self::API . '/about', ['fields' => 'user(emailAddress,displayName),storageQuota'])->json();
    }

    public function findFolder(BackupDestination $destination, string $name): ?array
    {
        $files = $this->request($destination, 'GET', self::API . '/files', [
            'q'      => "mimeType='application/vnd.google-apps.folder' and name='" . $this->esc($name) . "' and trashed=false",
            'fields' => 'files(id,name)',
            'spaces' => 'drive',
        ])->json('files', []);

        return $files[0] ?? null;
    }

    public function createFolder(BackupDestination $destination, string $name): array
    {
        return $this->request($destination, 'POST', self::API . '/files', ['fields' => 'id,name'], [
            'name'     => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
        ])->json();
    }

    public function fileMeta(BackupDestination $destination, string $fileId): ?array
    {
        $response = $this->send(fn () => $this->http($destination)->get(self::API . '/files/' . $fileId, [
            'fields' => 'id,name,size,md5Checksum,trashed,appProperties',
        ]));

        if ($response->status() === 404) {
            return null;
        }
        $this->assertOk($destination, $response);

        return $response->json();
    }

    /** Find a previously uploaded file in the folder carrying the same SHA-256. */
    public function findBySha256(BackupDestination $destination, string $folderId, string $sha256): ?array
    {
        $files = $this->request($destination, 'GET', self::API . '/files', [
            'q'      => "'" . $this->esc($folderId) . "' in parents and trashed=false and appProperties has { key='sha256' and value='" . $this->esc($sha256) . "' }",
            'fields' => 'files(id,name,size,md5Checksum)',
        ])->json('files', []);

        return $files[0] ?? null;
    }

    /**
     * Resumable, chunked upload. Survives transient failures per-chunk by asking Google
     * how much it already has and continuing from there.
     *
     * @return array{id:string,size:int,md5Checksum:?string}
     */
    public function upload(BackupDestination $destination, string $localPath, string $name, string $folderId, array $appProperties, ?callable $progress = null): array
    {
        $total = filesize($localPath);

        $init = $this->send(fn () => $this->http($destination)
            ->withHeaders(['X-Upload-Content-Type' => 'application/octet-stream', 'X-Upload-Content-Length' => (string) $total])
            ->post(self::UPLOAD . '/files?uploadType=resumable&fields=id,size,md5Checksum', [
                'name'          => $name,
                'parents'       => [$folderId],
                'appProperties' => $appProperties,
            ]));
        $this->assertOk($destination, $init);

        $session = $init->header('Location');
        if (! $session) {
            throw new BackupException('Google Drive did not return an upload session.', 'upload_failed', true);
        }

        $chunkSize = max(256 * 1024, (int) config('backup-manager.google.chunk_size'));
        $chunkSize -= $chunkSize % (256 * 1024);

        $handle = fopen($localPath, 'rb');
        $offset = 0;
        $failures = 0;

        try {
            while ($offset < $total) {
                fseek($handle, $offset);
                $data = fread($handle, min($chunkSize, $total - $offset));
                $end = $offset + strlen($data) - 1;

                try {
                    $response = Http::withHeaders(['Content-Range' => "bytes {$offset}-{$end}/{$total}"])
                        ->withBody($data, 'application/octet-stream')
                        ->timeout($this->timeout() * 2)
                        ->withoutRedirecting()
                        ->put($session);
                } catch (ConnectionException $e) {
                    if (++$failures > 5) {
                        throw new DestinationUnavailableException('Lost connection to Google Drive during upload.', $e);
                    }
                    sleep(min(30, 2 ** $failures));
                    $offset = $this->resumeOffset($session, $total) ?? $offset;
                    continue;
                }

                if (in_array($response->status(), [200, 201], true)) {
                    $offset = $total;
                    $progress && $progress($total, $total);
                    $result = $response->json();
                    break;
                }

                if ($response->status() === 308) {
                    $failures = 0;
                    $range = $response->header('Range'); // "bytes=0-1048575"
                    $offset = $range && preg_match('/-(\d+)$/', $range, $m) ? ((int) $m[1]) + 1 : $offset;
                    $progress && $progress($offset, $total);
                    continue;
                }

                if ($response->status() === 401) {
                    $this->flagAuthExpired($destination);
                    throw new AuthenticationExpiredException();
                }

                if ($response->serverError() || $response->status() === 429) {
                    if (++$failures > 5) {
                        throw new BackupException('Google Drive kept failing during upload: ' . $this->errorMessage($response), 'upload_failed', true);
                    }
                    sleep(min(30, 2 ** $failures));
                    $offset = $this->resumeOffset($session, $total) ?? $offset;
                    continue;
                }

                $this->assertOk($destination, $response);
            }
        } finally {
            fclose($handle);
        }

        if (! isset($result['id'])) {
            throw new BackupException('Upload finished without a confirmed Google Drive file ID.', 'upload_unconfirmed', true);
        }

        return $result;
    }

    public function stream(BackupDestination $destination, string $fileId)
    {
        $response = $this->send(fn () => Http::withToken($this->accessToken($destination))
            ->withOptions(['stream' => true])
            ->timeout(0)
            ->get(self::API . '/files/' . $fileId, ['alt' => 'media']));

        $this->assertOk($destination, $response);

        return \GuzzleHttp\Psr7\StreamWrapper::getResource($response->toPsrResponse()->getBody());
    }

    public function delete(BackupDestination $destination, string $fileId): void
    {
        $response = $this->send(fn () => $this->http($destination)->delete(self::API . '/files/' . $fileId));

        if ($response->status() === 404) {
            return; // already gone
        }
        $this->assertOk($destination, $response);
    }

    /* ------------------------------------------------------------- internals */

    protected function resumeOffset(string $session, int $total): ?int
    {
        try {
            $response = Http::withHeaders(['Content-Range' => "bytes */{$total}"])->withBody('', 'application/octet-stream')->withoutRedirecting()->put($session);
        } catch (\Throwable) {
            return null;
        }

        if ($response->status() === 308) {
            $range = $response->header('Range');

            return $range && preg_match('/-(\d+)$/', $range, $m) ? ((int) $m[1]) + 1 : 0;
        }

        return null;
    }

    /** Run a request and convert transport errors into a destination-unavailable failure. */
    protected function send(callable $callback): Response
    {
        try {
            return $callback();
        } catch (ConnectionException $e) {
            throw new DestinationUnavailableException('Could not reach Google Drive (is the internet connection available?).', $e);
        }
    }

    protected function assertOk(BackupDestination $destination, Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $reason = (string) data_get($response->json(), 'error.errors.0.reason', data_get($response->json(), 'error.status', ''));

        if ($response->status() === 401) {
            $this->flagAuthExpired($destination);
            throw new AuthenticationExpiredException();
        }

        if ($response->status() === 403 && in_array($reason, ['storageQuotaExceeded', 'quotaExceeded', 'RESOURCE_EXHAUSTED'], true)) {
            throw new InsufficientStorageException('Google Drive storage is full.');
        }

        if ($response->status() === 403 && in_array($reason, ['userRateLimitExceeded', 'rateLimitExceeded'], true) || $response->status() === 429 || $response->serverError()) {
            throw new BackupException('Google Drive is temporarily unavailable: ' . $this->errorMessage($response), 'drive_unavailable', true);
        }

        throw new BackupException('Google Drive error: ' . $this->errorMessage($response), 'drive_error', $response->status() !== 403 && $response->status() !== 404);
    }

    protected function flagAuthExpired(BackupDestination $destination): void
    {
        $destination->markStatus(BackupDestination::STATUS_AUTH_EXPIRED, 'Google Drive authentication expired. Please reconnect.');
    }

    protected function errorMessage(Response $response): string
    {
        // Never include tokens/secrets; only Google's own error text.
        return (string) (data_get($response->json(), 'error.message') ?: data_get($response->json(), 'error_description') ?: data_get($response->json(), 'error') ?: 'HTTP ' . $response->status());
    }

    protected function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new ConfigurationException('Google Drive credentials are not configured. Set BACKUP_GOOGLE_CLIENT_ID and BACKUP_GOOGLE_CLIENT_SECRET in .env.');
        }
    }

    protected function esc(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }

    protected function timeout(): int
    {
        return (int) config('backup-manager.google.timeout', 120);
    }
}
