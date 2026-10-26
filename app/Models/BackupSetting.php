<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton settings row for the Backup Manager.
 * The encryption password is encrypted with the app key and hidden from serialisation.
 */
class BackupSetting extends Model
{
    protected $table = 'backup_settings';

    protected $guarded = ['id'];

    protected $hidden = ['encryption_password', 'google_client_secret'];

    protected $casts = [
        'enabled'              => 'boolean',
        'schedule_enabled'     => 'boolean',
        'encryption_enabled'   => 'boolean',
        'notify_success'       => 'boolean',
        'notify_failure'       => 'boolean',
        'notify_recipients'    => 'array',
        'encryption_password'  => 'encrypted',
        'google_client_secret' => 'encrypted',
        'schedule_anchor_at'   => 'datetime',
        'day_of_week'          => 'integer',
        'day_of_month'         => 'integer',
    ];

    public function effectiveGoogleClientId(): ?string
    {
        return $this->google_client_id ?: config('backup-manager.google.client_id');
    }

    public function effectiveGoogleClientSecret(): ?string
    {
        return $this->google_client_secret ?: config('backup-manager.google.client_secret');
    }

    public function isGoogleOAuthConfigured(): bool
    {
        return filled($this->effectiveGoogleClientId()) && filled($this->effectiveGoogleClientSecret());
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'timezone' => config('app.timezone', 'UTC'),
        ]);
    }

    public function defaultDestination()
    {
        return $this->belongsTo(BackupDestination::class, 'default_destination_id');
    }
}
