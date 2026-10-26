<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Backup extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_DELETED = 'deleted';

    public const TRIGGER_MANUAL = 'manual';
    public const TRIGGER_SCHEDULED = 'scheduled';
    public const TRIGGER_SAFETY = 'restore_safety';

    protected $table = 'backups';

    protected $guarded = ['id'];

    protected $hidden = ['stage_path'];

    protected $casts = [
        'size_bytes'           => 'integer',
        'encrypted'            => 'boolean',
        'verification_details' => 'array',
        'meta'                 => 'array',
        'attempts'             => 'integer',
        'duration_seconds'     => 'integer',
        'started_at'           => 'datetime',
        'completed_at'         => 'datetime',
        'next_retry_at'        => 'datetime',
        'heartbeat_at'         => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Backup $backup) {
            $backup->uuid ??= (string) Str::uuid();
        });
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(BackupDestination::class, 'destination_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BackupLog::class, 'backup_id');
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESSFUL;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RUNNING], true);
    }

    /** A backup can be restored/downloaded only when it was stored and verified. */
    public function isUsable(): bool
    {
        return $this->isSuccessful() && $this->verification_status === 'passed';
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESSFUL);
    }

    public function triggeredBy(): string
    {
        return match ($this->trigger) {
            self::TRIGGER_SCHEDULED => 'Scheduler',
            self::TRIGGER_SAFETY    => 'Pre-restore safety backup',
            default                 => $this->creator?->name ?? 'System',
        };
    }

    public function humanSize(): string
    {
        return self::formatBytes($this->size_bytes);
    }

    public static function formatBytes(?int $bytes): string
    {
        if ($bytes === null) {
            return '-';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $size = (float) $bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, $i === 0 ? 0 : 2) . ' ' . $units[$i];
    }
}
