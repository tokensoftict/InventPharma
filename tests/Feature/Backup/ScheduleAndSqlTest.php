<?php

namespace Tests\Feature\Backup;

use App\Models\BackupSetting;
use App\Services\Backup\Dump\SqlRestoreExecutor;
use App\Services\Backup\ScheduleCalculator;
use Carbon\CarbonImmutable;

class ScheduleAndSqlTest extends BackupTestCase
{
    protected function calc(array $attrs): array
    {
        $s = $this->settings(array_merge(['enabled' => true, 'schedule_enabled' => true, 'timezone' => 'Africa/Lagos', 'schedule_anchor_at' => null], $attrs));

        return [app(ScheduleCalculator::class), $s];
    }

    public function test_daily_slot_is_in_configured_timezone(): void
    {
        [$c, $s] = $this->calc(['frequency' => 'daily', 'schedule_time' => '02:00']);
        $now = CarbonImmutable::parse('2026-03-10 03:00', 'Africa/Lagos');   // Lagos = UTC+1

        $slot = $c->latestSlot($s, $now);

        $this->assertSame('2026-03-10 02:00', $slot->timezone('Africa/Lagos')->format('Y-m-d H:i'));
        $this->assertSame('daily:2026-03-10T01:00', $c->slotKey($s, $slot));
    }

    public function test_same_slot_gives_same_key_so_runs_are_deduplicated(): void
    {
        [$c, $s] = $this->calc(['frequency' => 'daily', 'schedule_time' => '02:00']);

        $a = $c->dueSlot($s, CarbonImmutable::parse('2026-03-10 02:01', 'Africa/Lagos'));
        $b = $c->dueSlot($s, CarbonImmutable::parse('2026-03-10 02:05', 'Africa/Lagos'));

        $this->assertSame($c->slotKey($s, $a), $c->slotKey($s, $b));
    }

    public function test_nothing_due_when_manual_or_disabled(): void
    {
        [$c, $s] = $this->calc(['frequency' => 'manual']);
        $this->assertNull($c->dueSlot($s));

        [$c, $s] = $this->calc(['frequency' => 'daily', 'schedule_enabled' => false]);
        $this->assertNull($c->dueSlot($s));
    }

    public function test_saving_a_schedule_does_not_trigger_an_instant_backup(): void
    {
        [$c, $s] = $this->calc(['frequency' => 'every_hour', 'schedule_anchor_at' => now()]);

        $this->assertNull($c->dueSlot($s, now()->addMinutes(1)->toImmutable()));
    }

    public function test_weekly_and_monthly_have_a_next_run(): void
    {
        [$c, $s] = $this->calc(['frequency' => 'weekly', 'day_of_week' => 1, 'schedule_time' => '03:30']);
        $next = $c->nextRun($s, CarbonImmutable::parse('2026-03-10 12:00', 'Africa/Lagos'))->timezone('Africa/Lagos');
        $this->assertSame(1, $next->dayOfWeek);
        $this->assertSame('03:30', $next->format('H:i'));

        [$c, $s] = $this->calc(['frequency' => 'monthly', 'day_of_month' => 31, 'schedule_time' => '01:00']);
        $next = $c->nextRun($s, CarbonImmutable::parse('2026-02-10 12:00', 'Africa/Lagos'))->timezone('Africa/Lagos');
        $this->assertSame('2026-02-28', $next->format('Y-m-d'));   // short month -> last day
    }

    public function test_sql_splitter_handles_quotes_comments_delimiters_and_chunk_boundaries(): void
    {
        $sql = "-- comment; with semicolon\n"
            . "INSERT INTO t VALUES ('a;b', 'it''s; fine', \"x;y\");\n"
            . "/* block; comment */ CREATE TABLE `we;ird` (id int);\n"
            . "DELIMITER ;;\nCREATE TRIGGER x BEFORE INSERT ON t FOR EACH ROW BEGIN SET @a=1; SET @b=2; END;;\nDELIMITER ;\n"
            . "INSERT INTO t VALUES (" . str_repeat('9', 70000) . ");\n"
            . "SELECT 1";

        $gz = $this->tmp . '/s.sql.gz';
        file_put_contents($gz, gzencode($sql));
        $stream = gzopen($gz, 'rb');

        $exec = new SqlRestoreExecutor(new \PDO('sqlite::memory:'));
        $statements = array_values(array_filter(array_map('trim', iterator_to_array($exec->statements($stream), false))));
        gzclose($stream);

        $this->assertStringContainsString("'a;b'", $statements[0]);
        $this->assertStringContainsString("'it''s; fine'", $statements[0]);
        $this->assertStringContainsString('we;ird', implode("\n", $statements));
        $trigger = collect($statements)->first(fn ($x) => str_contains($x, 'CREATE TRIGGER'));
        $this->assertStringContainsString('SET @a=1; SET @b=2; END', $trigger);
        $this->assertTrue(collect($statements)->contains(fn ($x) => str_contains($x, str_repeat('9', 70000))));
        $this->assertSame('SELECT 1', end($statements));
    }

    public function test_backup_abilities_require_a_logged_in_user(): void
    {
        $this->assertFalse(app(\App\Services\Backup\BackupAuthorizer::class)->can('run'));
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(\App\Services\Backup\BackupAuthorizer::class)->authorize('restore');
    }
}
