<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * DATABASE CACHE MODEL — NOT THE SOURCE OF TRUTH.
 *
 * This model reflects the current signed activation for reporting/display.
 * All authoritative activation decisions must use ApplicationActivationService.
 * Manual changes to expires_at here will be detected as tampering.
 *
 * @property int    $id
 * @property string $installation_id
 * @property string|null $activation_id
 * @property string $product
 * @property \Carbon\Carbon|null $activated_at
 * @property \Carbon\Carbon|null $expires_at
 * @property string $status
 * @property string|null $notes
 */
class ApplicationLicense extends Model
{
    protected $table = 'application_licenses';

    protected $fillable = [
        'installation_id',
        'activation_id',
        'product',
        'activated_at',
        'expires_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];
}
