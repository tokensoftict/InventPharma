<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'backup_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'context'    => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
