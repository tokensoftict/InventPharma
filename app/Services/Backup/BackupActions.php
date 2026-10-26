<?php

namespace App\Services\Backup;

use App\Jobs\RestoreBackupJob;
use App\Models\Backup;
use App\Models\BackupDestination;
use App\Models\BackupSetting;
use App\Services\Backup\Drivers\GoogleDriveClient;
use App\Services\Backup\Drivers\GoogleDriveDriver;
use App\Services\Backup\Drivers\LocalDriver;
use App\Services\Backup\Exceptions\BackupException;
use App\Services\Backup\Exceptions\ConfigurationException;
use App\Services\Backup\Support\BackupEncryptor;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The single, authorised entry point for every user-triggered Backup Manager action.
 * Both the Livewire screens and the HTTP endpoints call this class, so authorization,
 * validation and audit logging cannot be bypassed by choosing a different UI path.
 */
class BackupActions
{
    public function __construct(
        protected BackupAuthorizer $auth,
        protected BackupService $backups,
        protected BackupDestinationManager $drivers,
        protected BackupLogger $log,
        protected GoogleDriveClient $google,
        protected StorageMonitor $storage,
    ) {
    }

    /* --------------------------------------------------------------- backups */

    public function backupNow(int $destinationId): Backup
    {
        $this->auth->authorize('run');

        if (! BackupSetting::current()->enabled) {
            throw new BackupException('The Backup Manager is disabled. Enable it in Backup Settings first.', 'disabled', false);
        }

        $destination = BackupDestination::findOrFail($destinationId);
        if (! $destination->enabled) {
            throw new BackupException("Destination \"{$destination->name}\" is not enabled.", 'destination_disabled', false);
        }

        [$backup] = $this->backups->createPending($destination, Backup::TRIGGER_MANUAL, auth()->id());
        $this->log->audit('backup_manual', "Manual backup #{$backup->id} started on {$destination->name}.", $backup);
        $this->backups->dispatch($backup);

        return $backup;
    }

    public function retry(int $backupId): Backup
    {
        $this->auth->authorize('run');
        $backup = $this->backups->retry(Backup::findOrFail($backupId));
        $this->log->audit('backup_retry', "Backup #{$backup->id} retried.", $backup);

        return $backup;
    }

    public function delete(int $backupId): void
    {
        $this->auth->authorize('delete');
        $this->backups->deleteBackup(Backup::findOrFail($backupId));
    }

    /**
     * Queue a restore. Requires the typed confirmation word as the second explicit confirmation.
     */
    public function restore(int $backupId, string $confirmation, ?string $password = null): Backup
    {
        $this->auth->authorize('restore');

        if (strtoupper(trim($confirmation)) !== 'RESTORE') {
            throw new BackupException('Type RESTORE to confirm.', 'confirmation_required', false);
        }

        $backup = Backup::with('destination')->findOrFail($backupId);
        if (! $backup->isUsable()) {
            throw new BackupException('Only successful, verified backups can be restored.', 'invalid_state', false);
        }

        $this->log->audit('restore_requested', "Restore of backup #{$backup->id} ({$backup->filename}) requested.", $backup);

        RestoreBackupJob::dispatch($backup->id, auth()->id(), $password ? Crypt::encryptString($password) : null);

        return $backup;
    }

    public function download(int $backupId): StreamedResponse
    {
        $this->auth->authorize('download');

        $backup = Backup::with('destination')->findOrFail($backupId);
        if (! $backup->isUsable() || ! $backup->destination) {
            throw new BackupException('This backup is not available for download.', 'invalid_state', false);
        }

        $driver = $this->drivers->driver($backup->destination);
        if (! $driver->supportsDownload()) {
            throw new BackupException('This destination does not support downloads through the application.', 'unsupported', false);
        }

        $this->log->audit('backup_downloaded', "Backup {$backup->filename} downloaded.", $backup);

        return response()->streamDownload(function () use ($driver, $backup) {
            $stream = $driver->readStream($backup->destination, $backup);
            while (! feof($stream)) {
                echo fread($stream, 1048576);
                flush();
            }
            fclose($stream);
        }, $backup->filename, [
            'Content-Type'  => 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /* -------------------------------------------------------------- settings */

    public function saveSettings(array $input): BackupSetting
    {
        $this->auth->authorize('configure');

        if (is_string($input['notify_recipients'] ?? null)) {
            $input['notify_recipients'] = array_values(array_filter(array_map('trim', preg_split('/[,;\s]+/', $input['notify_recipients']))));
        }

        $data = Validator::make($input, [
            'enabled'                => 'boolean',
            'default_destination_id' => 'nullable|exists:backup_destinations,id',
            'timezone'               => 'required|timezone',
            'schedule_enabled'       => 'boolean',
            'frequency'              => 'required|in:' . implode(',', array_keys(config('backup-manager.frequencies'))),
            'schedule_time'          => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'day_of_week'            => 'required|integer|between:0,6',
            'day_of_month'           => 'required|integer|between:1,31',
            'encryption_enabled'     => 'boolean',
            'encryption_password'    => 'nullable|string|min:8|max:200',
            'notify_success'         => 'boolean',
            'notify_failure'         => 'boolean',
            'notify_recipients'      => 'nullable|array|max:20',
            'notify_recipients.*'    => 'email',
        ])->validate();

        $settings = BackupSetting::current();
        $before = $settings->only(['enabled', 'default_destination_id', 'timezone', 'schedule_enabled', 'frequency', 'schedule_time', 'day_of_week', 'day_of_month', 'encryption_enabled', 'notify_success', 'notify_failure']);

        // ---- encryption
        $newPassword = $data['encryption_password'] ?? null;
        unset($data['encryption_password']);

        if (! empty($data['encryption_enabled'])) {
            if (! BackupEncryptor::available()) {
                throw new ConfigurationException('Encryption needs the PHP "sodium" extension, which is not enabled on this server.');
            }
            if (! $newPassword && ! $settings->encryption_password) {
                throw new ConfigurationException('Enter an encryption password to enable backup encryption.');
            }
        }
        if ($newPassword) {
            $data['encryption_password'] = $newPassword;               // encrypted by the model cast
            $data['encryption_fingerprint'] = BackupEncryptor::fingerprint($newPassword);
        }

        // ---- schedule anchor: saving a schedule must never trigger an instant surprise backup
        $scheduleFields = ['schedule_enabled', 'frequency', 'schedule_time', 'day_of_week', 'day_of_month', 'timezone'];
        $scheduleChanged = collect($scheduleFields)->contains(fn ($f) => (string) ($before[$f] ?? '') !== (string) ($data[$f] ?? ''));
        if ($scheduleChanged) {
            $data['schedule_anchor_at'] = now();
        }

        $settings->fill($data)->save();

        $changed = collect($before)->filter(fn ($v, $k) => (string) $v !== (string) ($data[$k] ?? $v))->keys()->all();
        if ($newPassword) {
            $changed[] = 'encryption_password';
        }

        $this->log->audit('settings_changed', 'Backup configuration changed.', null, ['changed' => $changed]);
        if (in_array('encryption_enabled', $changed, true)) {
            $this->log->audit($settings->encryption_enabled ? 'encryption_enabled' : 'encryption_disabled', 'Backup encryption ' . ($settings->encryption_enabled ? 'enabled.' : 'disabled.'));
        }

        return $settings;
    }

    /* ----------------------------------------------------------- destinations */

    /** Create/update the Local Storage destination and initialise (marker + checks) it. */
    public function saveLocalDestination(?int $id, array $input): BackupDestination
    {
        $this->auth->authorize('configure');

        $data = Validator::make($input, [
            'name'                => 'required|string|max:100',
            'path'                => 'required|string|max:500',
            'enabled'             => 'boolean',
            'retention_keep_last' => 'nullable|integer|min:1|max:1000',
            'retention_keep_days' => 'nullable|integer|min:1|max:3650',
        ])->validate();

        $destination = $id ? BackupDestination::where('type', BackupDestination::TYPE_LOCAL)->findOrFail($id) : new BackupDestination(['type' => BackupDestination::TYPE_LOCAL]);

        $pathChanged = $destination->exists && $destination->conf('path') !== $data['path'];
        $destination->name = $data['name'];
        $destination->retention_keep_last = $data['retention_keep_last'] ?? null;
        $destination->retention_keep_days = $data['retention_keep_days'] ?? null;
        $destination->setConf(['path' => rtrim($data['path'], "/\\")] + ($pathChanged ? ['marker' => null] : []));
        $destination->enabled = false;
        $destination->status = BackupDestination::STATUS_UNCONFIGURED;
        $destination->save();

        /** @var LocalDriver $driver */
        $driver = $this->drivers->driver($destination);

        try {
            $driver->initialize($destination);
            $driver->validate($destination, 0);
            $destination->markStatus(BackupDestination::STATUS_READY);
            $destination->forceFill(['enabled' => (bool) ($data['enabled'] ?? true)])->save();
        } catch (BackupException $e) {
            $destination->markStatus($e->errorCode === 'destination_unavailable' ? BackupDestination::STATUS_UNAVAILABLE : BackupDestination::STATUS_ERROR, $e->getMessage());
            $this->log->audit('destination_saved', "Local destination \"{$destination->name}\" saved but not usable: " . $e->getMessage(), null, ['destination_id' => $destination->id]);
            throw $e;
        }

        $this->log->audit('destination_saved', "Local destination \"{$destination->name}\" saved.", null, ['destination_id' => $destination->id, 'path' => $destination->conf('path')]);

        $this->ensureDefault($destination);

        return $destination;
    }

    public function saveRetention(int $destinationId, ?int $keepLast, ?int $keepDays): BackupDestination
    {
        $this->auth->authorize('configure');

        Validator::make(['l' => $keepLast, 'd' => $keepDays], ['l' => 'nullable|integer|min:1|max:1000', 'd' => 'nullable|integer|min:1|max:3650'])->validate();

        $destination = BackupDestination::findOrFail($destinationId);
        $destination->forceFill(['retention_keep_last' => $keepLast ?: null, 'retention_keep_days' => $keepDays ?: null])->save();
        $this->log->audit('retention_changed', "Retention for \"{$destination->name}\" changed.", null, ['destination_id' => $destination->id, 'keep_last' => $keepLast, 'keep_days' => $keepDays]);

        return $destination;
    }

    public function toggleDestination(int $destinationId): BackupDestination
    {
        $this->auth->authorize('configure');

        $destination = BackupDestination::findOrFail($destinationId);
        if (! $destination->enabled && $destination->status !== BackupDestination::STATUS_READY) {
            throw new BackupException('Test the destination successfully before enabling it.', 'not_ready', false);
        }
        $destination->forceFill(['enabled' => ! $destination->enabled])->save();
        $this->log->audit('destination_toggled', "Destination \"{$destination->name}\" " . ($destination->enabled ? 'enabled' : 'disabled') . '.', null, ['destination_id' => $destination->id]);

        return $destination;
    }

    /** "Test Destination": validates reachability, permissions and free space. Returns a short status message. */
    public function testDestination(int $destinationId): string
    {
        $this->auth->authorize('test');

        $destination = BackupDestination::findOrFail($destinationId);
        $driver = $this->drivers->driver($destination);

        try {
            if ($driver instanceof LocalDriver && ! $destination->conf('marker')) {
                $driver->initialize($destination);
            }
            $driver->validate($destination, $this->storage->requiredBytes());
            $destination->markStatus(BackupDestination::STATUS_READY);

            return 'Destination is ready.';
        } catch (BackupException $e) {
            $destination->markStatus(match ($e->errorCode) {
                'destination_unavailable' => BackupDestination::STATUS_UNAVAILABLE,
                'auth_expired'            => BackupDestination::STATUS_AUTH_EXPIRED,
                default                   => BackupDestination::STATUS_ERROR,
            }, $e->getMessage());

            throw $e;
        }
    }

    /* ----------------------------------------------------------- google drive */

    public function saveGoogleCredentials(?string $clientId, ?string $clientSecret): BackupSetting
    {
        $this->auth->authorize('configure');

        $settings = BackupSetting::current();
        $settings->google_client_id = trim((string) $clientId) ?: null;
        if ($clientSecret !== null && trim($clientSecret) !== '') {
            $settings->google_client_secret = trim($clientSecret);
        }
        $settings->save();

        $this->log->audit('google_credentials_saved', 'Google OAuth API credentials updated.');

        return $settings;
    }

    public function googleRedirectUri(): string
    {
        return route('backup.google.callback');
    }

    /** @return string the Google consent URL */
    public function googleConnectUrl(string $folderName = 'Inventory Backups'): string
    {
        $this->auth->authorize('google');

        $state = Str::random(40);
        session()->put('backup_google_state', ['state' => $state, 'folder' => trim($folderName) ?: 'Inventory Backups', 'at' => time()]);

        return $this->google->authUrl($state, $this->googleRedirectUri());
    }

    public function googleCallback(?string $code, ?string $state, ?string $error = null): BackupDestination
    {
        $this->auth->authorize('google');

        $saved = session()->pull('backup_google_state');
        if (! $saved || ! $state || ! hash_equals($saved['state'], $state) || time() - ($saved['at'] ?? 0) > 900) {
            throw new BackupException('The Google sign-in session is invalid or expired. Please start again.', 'oauth_state', false);
        }
        if ($error || ! $code) {
            throw new BackupException('Google Drive authorisation was cancelled or denied.', 'oauth_denied', false);
        }

        $tokens = $this->google->exchangeCode($code, $this->googleRedirectUri());

        $destination = BackupDestination::firstOrNew(['type' => BackupDestination::TYPE_GOOGLE_DRIVE]);
        $existingRefresh = $destination->conf('refresh_token');

        if (! $tokens['refresh_token'] && ! $existingRefresh) {
            throw new BackupException('Google did not return a refresh token. Remove this app from your Google account permissions and connect again.', 'oauth_no_refresh', false);
        }

        $destination->name = $destination->name ?: 'Google Drive';
        $destination->setConf([
            'access_token'  => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?: $existingRefresh,
            'expires_at'    => $tokens['expires_at'],
        ]);
        $destination->status = BackupDestination::STATUS_UNCONFIGURED;
        $destination->save();

        /** @var GoogleDriveDriver $driver */
        $driver = $this->drivers->driver($destination);
        $about = $this->google->about($destination);
        $driver->ensureFolder($destination, $saved['folder']);

        $destination->setConf(['account' => $about['user']['emailAddress'] ?? null]);
        $destination->enabled = true;
        $destination->save();
        $destination->markStatus(BackupDestination::STATUS_READY);

        $this->log->audit('google_connected', 'Google Drive connected.', null, ['destination_id' => $destination->id, 'account' => $destination->conf('account')]);
        $this->ensureDefault($destination);

        return $destination;
    }

    public function googleDisconnect(int $destinationId): void
    {
        $this->auth->authorize('disconnect');

        $destination = BackupDestination::where('type', BackupDestination::TYPE_GOOGLE_DRIVE)->findOrFail($destinationId);

        $this->google->revoke($destination->conf('refresh_token') ?: $destination->conf('access_token'));

        $destination->config = null;            // wipes tokens, account, folder
        $destination->enabled = false;
        $destination->save();
        $destination->markStatus(BackupDestination::STATUS_UNCONFIGURED, 'Disconnected.');

        $settings = BackupSetting::current();
        if ($settings->default_destination_id === $destination->id) {
            $settings->forceFill(['default_destination_id' => null])->save();
        }

        $this->log->audit('google_disconnected', 'Google Drive disconnected.', null, ['destination_id' => $destination->id]);
    }

    protected function ensureDefault(BackupDestination $destination): void
    {
        $settings = BackupSetting::current();
        if (! $settings->default_destination_id && $destination->enabled) {
            $settings->forceFill(['default_destination_id' => $destination->id])->save();
        }
    }
}
