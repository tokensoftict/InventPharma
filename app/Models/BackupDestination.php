<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class BackupDestination
 *
 * `config` holds secrets (OAuth tokens, paths) and is encrypted at rest with the
 * application key. It is hidden from array/JSON serialisation so it can never leak
 * into a Livewire payload or API response.
 */
class BackupDestination extends Model
{
    public const TYPE_LOCAL = 'local';
    public const TYPE_GOOGLE_DRIVE = 'google_drive';

    public const STATUS_UNCONFIGURED = 'unconfigured';
    public const STATUS_READY = 'ready';
    public const STATUS_UNAVAILABLE = 'unavailable';
    public const STATUS_AUTH_EXPIRED = 'auth_expired';
    public const STATUS_ERROR = 'error';

    protected $table = 'backup_destinations';

    protected $guarded = ['id'];

    protected $hidden = ['config'];

    protected $casts = [
        'config'                    => 'encrypted:array',
        'enabled'                   => 'boolean',
        'retention_keep_last'       => 'integer',
        'retention_keep_days'       => 'integer',
        'last_checked_at'           => 'datetime',
        'last_successful_backup_at' => 'datetime',
        'last_failure_at'           => 'datetime',
    ];

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class, 'destination_id');
    }

    public function conf(string $key, mixed $default = null): mixed
    {
        return data_get($this->config ?? [], $key, $default);
    }

    public function setConf(array $values): void
    {
        $this->config = array_merge($this->config ?? [], $values);
    }

    public function isUsable(): bool
    {
        return $this->enabled && $this->status === self::STATUS_READY;
    }

    public function markStatus(string $status, ?string $message = null): void
    {
        $this->forceFill([
            'status'          => $status,
            'status_message'  => $message ? mb_substr($message, 0, 500) : null,
            'last_checked_at' => now(),
        ])->save();
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_LOCAL        => 'Local Storage',
            self::TYPE_GOOGLE_DRIVE => 'Google Drive',
            default                 => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
}
