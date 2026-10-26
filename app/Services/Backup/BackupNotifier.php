<?php

namespace App\Services\Backup;

use App\Models\BackupSetting;
use App\Notifications\BackupEventNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Sends backup notifications. Notification failures (e.g. SMTP down) are swallowed:
 * they must never fail or retry a backup.
 */
class BackupNotifier
{
    /** event => [setting flag that gates it (null = always when recipients exist), level] */
    protected const EVENTS = [
        'backup_success'        => ['notify_success', 'info'],
        'backup_failed'         => ['notify_failure', 'error'],
        'destination_unavailable' => ['notify_failure', 'error'],
        'auth_expired'          => ['notify_failure', 'error'],
        'low_storage'           => ['notify_failure', 'warning'],
        'retention_cleanup'     => [null, 'info'],
        'restore_completed'     => [null, 'info'],
        'restore_failed'        => [null, 'error'],
    ];

    public function notify(string $event, string $subject, string $body): void
    {
        try {
            [$flag, $level] = self::EVENTS[$event] ?? ['notify_failure', 'info'];
            $settings = BackupSetting::current();

            if ($flag && ! $settings->{$flag}) {
                return;
            }

            $recipients = array_values(array_filter((array) $settings->notify_recipients, fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
            if (! $recipients) {
                return;
            }

            Notification::route('mail', $recipients)->notify(new BackupEventNotification($subject, $body, $level));
        } catch (\Throwable $e) {
            Log::warning('Backup notification not sent: ' . $e->getMessage());
        }
    }
}
