<?php

namespace App\Services\Backup;

use Illuminate\Auth\Access\AuthorizationException;

/**
 * Authorization for the Backup Manager.
 *
 * The application maps permissions to route names (tasks) and exposes them through
 * userCanView(). Its PermitTask middleware does NOT abort on a missing permission,
 * so every backup action enforces access here, server-side, itself.
 */
class BackupAuthorizer
{
    /** ability => route-name task */
    public const ABILITIES = [
        'view'       => 'backup.index',
        'history'    => 'backup.history',
        'settings'   => 'backup.settings',
        'configure'  => 'backup.configure',
        'run'        => 'backup.run',
        'download'   => 'backup.download',
        'delete'     => 'backup.delete',
        'restore'    => 'backup.restore',
        'test'       => 'backup.test',
        'google'     => 'backup.google.connect',
        'disconnect' => 'backup.google.disconnect',
    ];

    /** Abilities that additionally require the administrator group. */
    protected const ADMIN_ONLY = ['restore'];

    public function can(string $ability): bool
    {
        $user = auth()->user();
        if (! $user || ! isset(self::ABILITIES[$ability])) {
            return false;
        }

        if (in_array($ability, self::ADMIN_ONLY, true) && (int) $user->usergroup_id !== 1) {
            return false;
        }

        return (bool) userCanView(self::ABILITIES[$ability]);
    }

    public function authorize(string $ability): void
    {
        if (! $this->can($ability)) {
            throw new AuthorizationException('You are not authorised to perform this backup action.');
        }
    }
}
