<?php

namespace App\Services\Backup;

use App\Models\Backup;
use App\Models\BackupLog;
use Illuminate\Support\Facades\Log;

/**
 * Writes backup events and the audit trail to backup_logs (the application has no
 * general-purpose audit log - logActivity() is invoice specific - so this is the
 * single place for backup history, scoped to this module).
 *
 * Secrets are scrubbed from every context payload before it is stored.
 */
class BackupLogger
{
    public function event(?Backup $backup, string $event, string $message = '', string $level = 'info', array $context = []): BackupLog
    {
        return $this->write($backup, $event, $message, $level, 'event', $context);
    }

    /** Sensitive operation: records who did it and from where. */
    public function audit(string $event, string $message = '', ?Backup $backup = null, array $context = []): BackupLog
    {
        return $this->write($backup, $event, $message, 'info', 'audit', $context);
    }

    protected function write(?Backup $backup, string $event, string $message, string $level, string $category, array $context): BackupLog
    {
        $request = app()->runningInConsole() ? null : request();

        try {
            return BackupLog::create([
                'backup_id'      => $backup?->id,
                'destination_id' => $backup?->destination_id ?? ($context['destination_id'] ?? null),
                'user_id'        => auth()->check() ? auth()->id() : ($backup?->created_by),
                'level'          => $level,
                'category'       => $category,
                'event'          => $event,
                'message'        => mb_substr($message, 0, 2000),
                'context'        => $this->scrub($context) ?: null,
                'ip_address'     => $request?->ip(),
            ]);
        } catch (\Throwable $e) {
            // Logging must never take a backup down.
            Log::warning('Backup log write failed: ' . $e->getMessage());

            return new BackupLog();
        }
    }

    protected function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match('/pass(word)?|token|secret|key|credential|authorization/i', $key)) {
                $data[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $data[$key] = $this->scrub($value);
            }
        }

        return $data;
    }
}
