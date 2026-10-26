<?php

namespace App\Livewire\Settings\Backup;

use App\Models\BackupDestination;
use App\Models\BackupSetting;
use App\Services\Backup\BackupActions;
use App\Services\Backup\BackupAuthorizer;
use App\Services\Backup\Exceptions\BackupException;
use App\Services\Backup\Support\BackupEncryptor;
use App\Traits\LivewireAlert;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class BackupSettingsComponent extends Component
{
    use LivewireAlert;

    // general / schedule / security / notifications
    public bool $enabled = false;
    public ?int $default_destination_id = null;
    public string $timezone = 'Africa/Lagos';
    public bool $schedule_enabled = false;
    public string $frequency = 'daily';
    public string $schedule_time = '02:00';
    public int $day_of_week = 0;
    public int $day_of_month = 1;
    public bool $encryption_enabled = false;
    public string $encryption_password = '';
    public bool $notify_success = false;
    public bool $notify_failure = true;
    public string $notify_recipients = '';

    // local destination form
    public ?int $localId = null;
    public string $localName = 'Local Storage';
    public string $localPath = '';
    public bool $localEnabled = true;
    public ?int $localKeepLast = null;
    public ?int $localKeepDays = null;

    // google
    public string $googleFolder = 'Inventory Backups';
    public bool $configuringGoogle = false;
    public string $googleClientId = '';
    public string $googleClientSecret = '';

    // per-destination retention [id => ['last'=>, 'days'=>]]
    public array $retention = [];

    // server folder browser state
    public bool $browsingFolder = false;
    public string $currentBrowsePath = '';
    public array $browseFolders = [];
    public ?string $browseParent = null;
    public bool $browseIsWritable = false;
    public ?int $browseFreeBytes = null;
    public string $newFolderName = '';

    public function mount(BackupAuthorizer $auth)
    {
        abort_unless($auth->can('settings'), 403);

        $this->loadSettings();
        $this->loadLocal();

        if ($msg = session('backup_success')) {
            $this->toast('success', $msg);
        }
        if ($msg = session('backup_error')) {
            $this->toast('error', $msg);
        }
    }

    protected function loadSettings(): void
    {
        $s = BackupSetting::current();
        $this->enabled = (bool) $s->enabled;
        $this->default_destination_id = $s->default_destination_id;
        $this->timezone = $s->timezone;
        $this->schedule_enabled = (bool) $s->schedule_enabled;
        $this->frequency = $s->frequency;
        $this->schedule_time = $s->schedule_time;
        $this->day_of_week = (int) $s->day_of_week;
        $this->day_of_month = (int) $s->day_of_month;
        $this->encryption_enabled = (bool) $s->encryption_enabled;
        $this->notify_success = (bool) $s->notify_success;
        $this->notify_failure = (bool) $s->notify_failure;
        $this->notify_recipients = implode(', ', $s->notify_recipients ?? []);

        foreach (BackupDestination::all() as $d) {
            $this->retention[$d->id] = ['last' => $d->retention_keep_last, 'days' => $d->retention_keep_days];
        }
    }

    protected function loadLocal(): void
    {
        $local = BackupDestination::where('type', BackupDestination::TYPE_LOCAL)->orderBy('id')->first();
        if ($local && ! $this->localId) {
            $this->localId = $local->id;
            $this->localName = $local->name;
            $this->localPath = (string) $local->conf('path', '');
            $this->localEnabled = (bool) $local->enabled;
            $this->localKeepLast = $local->retention_keep_last;
            $this->localKeepDays = $local->retention_keep_days;
        }
    }

    /* --------------------------------------------------------------- actions */

    public function save(BackupActions $actions)
    {
        $this->guard(function () use ($actions) {
            $actions->saveSettings([
                'enabled'                => $this->enabled,
                'default_destination_id' => $this->default_destination_id ?: null,
                'timezone'               => $this->timezone,
                'schedule_enabled'       => $this->schedule_enabled,
                'frequency'              => $this->frequency,
                'schedule_time'          => $this->schedule_time,
                'day_of_week'            => $this->day_of_week,
                'day_of_month'           => $this->day_of_month,
                'encryption_enabled'     => $this->encryption_enabled,
                'encryption_password'    => $this->encryption_password ?: null,
                'notify_success'         => $this->notify_success,
                'notify_failure'         => $this->notify_failure,
                'notify_recipients'      => $this->notify_recipients,
            ]);
            $this->encryption_password = '';
            $this->toast('success', 'Backup settings saved.');
        });
    }

    public function saveLocal(BackupActions $actions)
    {
        $this->guard(function () use ($actions) {
            $d = $actions->saveLocalDestination($this->localId, [
                'name'                => $this->localName,
                'path'                => $this->localPath,
                'enabled'             => $this->localEnabled,
                'retention_keep_last' => $this->localKeepLast ?: null,
                'retention_keep_days' => $this->localKeepDays ?: null,
            ]);
            $this->localId = $d->id;
            $this->retention[$d->id] = ['last' => $d->retention_keep_last, 'days' => $d->retention_keep_days];
            $this->toast($d->status === BackupDestination::STATUS_READY ? 'success' : 'warning', $d->status === BackupDestination::STATUS_READY ? 'Local destination saved and verified.' : ($d->status_message ?: 'Saved, but the destination is not ready.'));
        });
    }

    public function saveRetention(BackupActions $actions, int $id)
    {
        $this->guard(function () use ($actions, $id) {
            $r = $this->retention[$id] ?? [];
            $actions->saveRetention($id, ($r['last'] ?? null) ?: null, ($r['days'] ?? null) ?: null);
            $this->toast('success', 'Retention saved.');
        });
    }

    public function toggle(BackupActions $actions, int $id)
    {
        $this->guard(fn () => $actions->toggleDestination($id));
    }

    public function test(BackupActions $actions, int $id)
    {
        $this->guard(fn () => $this->toast('success', $actions->testDestination($id)));
    }

    public function connectGoogle(BackupActions $actions)
    {
        try {
            return redirect()->away($actions->googleConnectUrl($this->googleFolder));
        } catch (\Throwable $e) {
            $this->guard(fn () => throw $e);
        }
    }

    public function disconnectGoogle(BackupActions $actions, int $id)
    {
        $this->guard(function () use ($actions, $id) {
            $actions->googleDisconnect($id);
            $this->toast('success', 'Google Drive disconnected.');
        });
    }

    /* ---------------------------------------------------- google OAuth setup */

    public function openGoogleConfig(BackupAuthorizer $auth): void
    {
        abort_unless($auth->can('configure'), 403);
        $s = BackupSetting::current();
        $this->googleClientId = (string) ($s->google_client_id ?: config('backup-manager.google.client_id', ''));
        $this->googleClientSecret = '';
        $this->configuringGoogle = true;
    }

    public function closeGoogleConfig(): void
    {
        $this->configuringGoogle = false;
        $this->googleClientSecret = '';
    }

    public function saveGoogleCredentials(BackupActions $actions): void
    {
        $this->guard(function () use ($actions) {
            if (empty(trim($this->googleClientId))) {
                throw new \InvalidArgumentException('Please enter a Google Client ID.');
            }
            $actions->saveGoogleCredentials($this->googleClientId, $this->googleClientSecret ?: null);
            $this->googleClientSecret = '';
            $this->configuringGoogle = false;
            $this->toast('success', 'Google OAuth credentials saved successfully.');
        });
    }

    public function saveGoogleAndConnect(BackupActions $actions)
    {
        try {
            if (empty(trim($this->googleClientId))) {
                $this->toast('warning', 'Please enter a Google Client ID.');
                return;
            }
            $actions->saveGoogleCredentials($this->googleClientId, $this->googleClientSecret ?: null);
            $this->googleClientSecret = '';
            $this->configuringGoogle = false;

            return redirect()->away($actions->googleConnectUrl($this->googleFolder));
        } catch (\Throwable $e) {
            $this->guard(fn () => throw $e);
        }
    }

    /* -------------------------------------------------------- folder browser */

    public function openFolderBrowser(BackupAuthorizer $auth, ?string $start = null): void
    {
        abort_unless($auth->can('configure'), 403);

        $path = $start ?: trim($this->localPath);
        if ($path === '' || ! is_dir($path)) {
            $path = storage_path('app/backups');
        }
        if (! is_dir($path)) {
            @mkdir($path, 0750, true);
        }

        $this->newFolderName = '';
        $this->browseTo($path);
        $this->browsingFolder = true;
    }

    public function browseTo(string $path): void
    {
        $path = rtrim($path, "/\\");
        if ($path === '' || ! is_dir($path)) {
            $this->toast('warning', 'Directory does not exist or cannot be accessed.');
            return;
        }

        // Never browse inside public directory
        $public = realpath(public_path());
        $real = realpath($path) ?: $path;
        if ($public && str_starts_with(str_replace('\\', '/', $real), str_replace('\\', '/', $public))) {
            $this->toast('error', 'Cannot browse or select directories inside the public web folder.');
            return;
        }

        $this->currentBrowsePath = $path;
        $this->browseIsWritable = is_writable($path);
        $free = @disk_free_space($path);
        $this->browseFreeBytes = $free !== false ? (int) $free : null;

        // Parent path
        $parent = dirname($path);
        $this->browseParent = ($parent !== $path && is_dir($parent)) ? $parent : null;

        // List subdirectories
        $folders = [];
        try {
            $items = @scandir($path) ?: [];
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                if (str_starts_with($item, '.') && $item !== '.inventory-backup-destination') {
                    continue;
                }
                $full = $path . DIRECTORY_SEPARATOR . $item;
                if (is_dir($full)) {
                    $folders[] = [
                        'name'     => $item,
                        'path'     => $full,
                        'writable' => is_writable($full),
                    ];
                }
            }
        } catch (\Throwable $e) {
            $this->toast('error', 'Unable to list directories: ' . $e->getMessage());
        }

        usort($folders, fn ($a, $b) => strcasecmp($a['name'], $b['name']));
        $this->browseFolders = $folders;
    }

    public function selectBrowsedFolder(?string $path = null): void
    {
        $selected = $path ?: $this->currentBrowsePath;
        $this->localPath = $selected;
        $this->browsingFolder = false;
        $this->toast('success', "Selected folder: {$selected}");
    }

    public function createFolderInBrowse(): void
    {
        $name = trim($this->newFolderName);
        if ($name === '' || str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, '..')) {
            $this->toast('warning', 'Please enter a valid folder name without slashes.');
            return;
        }

        $newPath = $this->currentBrowsePath . DIRECTORY_SEPARATOR . $name;
        if (is_dir($newPath)) {
            $this->toast('warning', 'Folder already exists.');
            return;
        }

        if (! @mkdir($newPath, 0750, true) && ! is_dir($newPath)) {
            $this->toast('error', 'Could not create folder. Check directory permissions.');
            return;
        }

        $this->newFolderName = '';
        $this->browseTo($newPath);
        $this->toast('success', "Folder \"{$name}\" created.");
    }

    public function closeFolderBrowser(): void
    {
        $this->browsingFolder = false;
    }

    public function detectedStorageLocations(): array
    {
        $locations = [];

        // Application internal backup folder
        $defaultStorage = storage_path('app/backups');
        $locations[] = ['name' => 'App Storage (default)', 'path' => $defaultStorage];

        if (PHP_OS_FAMILY === 'Darwin') {
            if (is_dir('/Volumes')) {
                foreach (@scandir('/Volumes') ?: [] as $f) {
                    if ($f[0] !== '.' && is_dir("/Volumes/{$f}")) {
                        $locations[] = ['name' => "External / Volume: {$f}", 'path' => "/Volumes/{$f}"];
                    }
                }
            }
        } elseif (PHP_OS_FAMILY === 'Linux') {
            foreach (['/media', '/mnt'] as $base) {
                if (is_dir($base)) {
                    foreach (@scandir($base) ?: [] as $sub) {
                        if ($sub[0] !== '.' && is_dir("{$base}/{$sub}")) {
                            $subPath = "{$base}/{$sub}";
                            $locations[] = ['name' => "Mount: {$sub}", 'path' => $subPath];
                            foreach (@scandir($subPath) ?: [] as $sub2) {
                                if ($sub2[0] !== '.' && is_dir("{$subPath}/{$sub2}")) {
                                    $locations[] = ['name' => "USB Drive: {$sub2}", 'path' => "{$subPath}/{$sub2}"];
                                }
                            }
                        }
                    }
                }
            }
            if (is_dir('/var/backups')) {
                $locations[] = ['name' => 'System Backups (/var/backups)', 'path' => '/var/backups'];
            }
        } elseif (PHP_OS_FAMILY === 'Windows') {
            foreach (range('C', 'Z') as $letter) {
                $drive = "{$letter}:\\";
                if (is_dir($drive)) {
                    $locations[] = ['name' => "Drive {$letter}:", 'path' => $drive];
                }
            }
        }

        return $locations;
    }

    /* ---------------------------------------------------------------- render */

    public function render(BackupActions $actions, BackupAuthorizer $auth)
    {
        $destinations = BackupDestination::orderBy('id')->get();

        return view('livewire.settings.backup.backup-settings-component', [
            'destinations'      => $destinations,
            'google'            => $destinations->where('type', BackupDestination::TYPE_GOOGLE_DRIVE),
            'frequencies'       => config('backup-manager.frequencies'),
            'timezones'         => \DateTimeZone::listIdentifiers(),
            'sodium'            => BackupEncryptor::available(),
            'hasPassword'       => (bool) BackupSetting::current()->encryption_password,
            'redirectUri'       => $actions->googleRedirectUri(),
            'googleReady'       => BackupSetting::current()->isGoogleOAuthConfigured(),
            'hasCustomGoogleCredentials' => filled(BackupSetting::current()->google_client_id),
            'googleClientIdConfigured' => BackupSetting::current()->effectiveGoogleClientId(),
            'detectedLocations' => $this->detectedStorageLocations(),
            'canConfigure'      => $auth->can('configure'),
            'canTest'           => $auth->can('test'),
            'canGoogle'         => $auth->can('google'),
            'canDisconnect'     => $auth->can('disconnect'),
        ]);
    }

    protected function guard(\Closure $callback): void
    {
        try {
            $callback();
        } catch (ValidationException $e) {
            $this->toast('error', collect($e->errors())->flatten()->first());
        } catch (AuthorizationException|BackupException $e) {
            $this->toast('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $this->toast('error', 'Something went wrong. See the backup log for details.');
        }
    }

    protected function toast(string $type, string $message): void
    {
        $this->alert($type, 'Backup Manager', ['position' => 'center', 'timer' => 4000, 'toast' => false, 'text' => $message]);
    }
}
