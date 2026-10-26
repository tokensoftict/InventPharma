<?php

namespace App\Services\Backup;

use App\Models\BackupSetting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Computes schedule slots in the configured timezone.
 *
 * A "slot" is one intended run time. Each slot has a deterministic key which is
 * stored in a unique index (destination_id, schedule_key) - so however many times
 * the scheduler fires for the same slot, at most one backup can ever be created.
 */
class ScheduleCalculator
{
    /** How long after its slot a missed run is still executed (server was down). */
    protected function catchUpHours(string $frequency): int
    {
        return match ($frequency) {
            'every_hour'     => 2,
            'every_6_hours'  => 12,
            'every_12_hours' => 24,
            default          => 36,
        };
    }

    public function timezone(BackupSetting $s): string
    {
        return in_array($s->timezone, timezone_identifiers_list(), true) ? $s->timezone : config('app.timezone', 'UTC');
    }

    public function isScheduled(BackupSetting $s): bool
    {
        return $s->enabled && $s->schedule_enabled && $s->frequency !== 'manual' && isset(config('backup-manager.frequencies')[$s->frequency]);
    }

    /** Most recent slot at or before $now, or null. */
    public function latestSlot(BackupSetting $s, ?CarbonInterface $now = null): ?CarbonImmutable
    {
        $now = $this->now($s, $now);
        $latest = null;

        foreach ($this->slots($s, $now->subDays(35), $now) as $slot) {
            if ($slot <= $now) {
                $latest = $slot;
            }
        }

        return $latest;
    }

    public function nextRun(BackupSetting $s, ?CarbonInterface $now = null): ?CarbonImmutable
    {
        if (! $this->isScheduled($s)) {
            return null;
        }

        $now = $this->now($s, $now);

        foreach ($this->slots($s, $now->startOfDay(), $now->addDays(35)) as $slot) {
            if ($slot > $now) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * The slot that should be executed right now, or null.
     * Slots at/before the schedule anchor (when the schedule was last edited) are ignored,
     * so saving a schedule never triggers an immediate surprise backup.
     */
    public function dueSlot(BackupSetting $s, ?CarbonInterface $now = null): ?CarbonImmutable
    {
        if (! $this->isScheduled($s)) {
            return null;
        }

        $now = $this->now($s, $now);
        $slot = $this->latestSlot($s, $now);

        if (! $slot) {
            return null;
        }
        if ($s->schedule_anchor_at && $slot <= CarbonImmutable::instance($s->schedule_anchor_at)) {
            return null;
        }
        if ($slot->diffInHours($now, true) > $this->catchUpHours($s->frequency)) {
            return null;
        }

        return $slot;
    }

    public function slotKey(BackupSetting $s, CarbonImmutable $slot): string
    {
        return $s->frequency . ':' . $slot->utc()->format('Y-m-d\TH:i');
    }

    /** @return \Generator<CarbonImmutable> slots in ascending order within [$from, $to] */
    protected function slots(BackupSetting $s, CarbonImmutable $from, CarbonImmutable $to): \Generator
    {
        [$h, $m] = array_map('intval', explode(':', $s->schedule_time ?: '02:00') + [0, 0]);

        $hours = match ($s->frequency) {
            'every_hour'     => range(0, 23),
            'every_6_hours'  => array_map(fn ($i) => ($h % 6) + 6 * $i, range(0, 3)),
            'every_12_hours' => array_map(fn ($i) => ($h % 12) + 12 * $i, range(0, 1)),
            default          => [$h],
        };

        for ($day = $from->startOfDay(); $day <= $to; $day = $day->addDay()->startOfDay()) {
            if ($s->frequency === 'weekly' && $day->dayOfWeek !== (int) $s->day_of_week) {
                continue;
            }
            if ($s->frequency === 'monthly' && $day->day !== min((int) $s->day_of_month, $day->daysInMonth)) {
                continue;
            }

            foreach ($hours as $hour) {
                $slot = $day->setTime($hour, $m, 0);
                if ($slot >= $from && $slot <= $to) {
                    yield $slot;
                }
            }
        }
    }

    protected function now(BackupSetting $s, ?CarbonInterface $now): CarbonImmutable
    {
        return CarbonImmutable::instance($now ?? now())->setTimezone($this->timezone($s));
    }
}
