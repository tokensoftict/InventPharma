<?php

namespace Tests\Feature\Backup;

use App\Jobs\RunBackupJob;
use App\Models\Backup;
use App\Models\BackupDestination;
use App\Services\Backup\BackupService;
use App\Services\Backup\Dump\DatabaseDumpService;
use App\Services\Backup\RetentionService;
use App\Services\Backup\Support\BackupEncryptor;
use Illuminate\Support\Facades\Queue;

class BackupFlowTest extends BackupTestCase
{
    protected function service(): BackupService
    {
        return app(BackupService::class);
    }

    public function test_local_backup_succeeds_with_checksum_and_verified_copy(): void
    {
        Queue::fake();
        $this->settings(['enabled' => true]);
        $dest = $this->localDestination();

        [$backup] = $this->service()->createPending($dest, Backup::TRIGGER_MANUAL, 1);
        $done = $this->service()->run($backup);

        $this->assertSame(Backup::STATUS_SUCCESSFUL, $done->status);
        $this->assertSame('passed', $done->verification_status);
        $this->assertMatchesRegularExpression('/^inventory_backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql\.gz$/', $done->filename);
        $file = $this->tmp . '/dest/' . $done->filename;
        $this->assertFileExists($file);
        $this->assertSame(hash_file('sha256', $file), $done->checksum_sha256);
        $this->assertSame(filesize($file), $done->size_bytes);
        $this->assertFileDoesNotExist($file . '.part');
    }

    public function test_failed_dump_is_recorded_and_retry_is_scheduled_with_increasing_delay(): void
    {
        Queue::fake();
        $this->settings(['enabled' => true]);
        $dest = $this->localDestination();
        app(DatabaseDumpService::class)->fake->fail = true;

        [$backup] = $this->service()->createPending($dest, Backup::TRIGGER_MANUAL, 1);

        $backup = $this->service()->run($backup);
        $this->assertSame(Backup::STATUS_PENDING, $backup->status);
        $this->assertSame(1, $backup->attempts);

        $this->service()->run($backup);
        $this->service()->run($backup);
        $backup = $this->service()->run($backup);   // 4th attempt = 3 retries exhausted

        $this->assertSame(Backup::STATUS_FAILED, $backup->status);
        $this->assertSame(4, $backup->attempts);
        $this->assertNotEmpty($backup->error_message);
        Queue::assertPushed(RunBackupJob::class, 3);
        $this->assertSame([], glob($this->tmp . '/dest/inventory_backup_*'));
    }

    public function test_failed_backup_never_overwrites_a_good_one(): void
    {
        Queue::fake();
        $this->settings(['enabled' => true]);
        $dest = $this->localDestination();

        [$good] = $this->service()->createPending($dest, Backup::TRIGGER_MANUAL, 1);
        $good = $this->service()->run($good);
        $before = hash_file('sha256', $this->tmp . '/dest/' . $good->filename);

        app(DatabaseDumpService::class)->fake->fail = true;
        [$bad] = $this->service()->createPending($dest, Backup::TRIGGER_MANUAL, 1);
        $this->service()->run($bad, false);

        $this->assertSame(Backup::STATUS_FAILED, $bad->fresh()->status);
        $this->assertSame($before, hash_file('sha256', $this->tmp . '/dest/' . $good->filename));
        $this->assertSame(Backup::STATUS_SUCCESSFUL, $good->fresh()->status);
    }

    public function test_missing_external_drive_is_reported_clearly(): void
    {
        Queue::fake();
        $this->settings(['enabled' => true]);
        $dest = $this->localDestination();
        unlink($this->tmp . '/dest/.inventory-backup-destination');   // simulates a replaced / unplugged drive

        [$backup] = $this->service()->createPending($dest, Backup::TRIGGER_MANUAL, 1);
        $backup = $this->service()->run($backup, false);

        $this->assertSame(Backup::STATUS_FAILED, $backup->status);
        $this->assertSame(BackupDestination::STATUS_UNAVAILABLE, $dest->fresh()->status);
    }

    public function test_insufficient_storage_fails_without_dumping(): void
    {
        Queue::fake();
        $this->settings(['enabled' => true]);
        $dest = $this->localDestination();
        app()->instance(DatabaseDumpService::class, new class(app(DatabaseDumpService::class)->fake) extends FakeDumpService {
            public function estimateBytes(): int
            {
                return PHP_INT_MAX / 4;
            }
        });

        [$backup] = $this->service()->createPending($dest, Backup::TRIGGER_MANUAL, 1);
        $backup = $this->service()->run($backup, false);

        $this->assertSame(Backup::STATUS_FAILED, $backup->status);
        $this->assertStringContainsStringIgnoringCase('insufficient', $backup->error_message);
    }

    public function test_scheduled_slot_is_idempotent(): void
    {
        Queue::fake();
        $dest = $this->localDestination();

        [$a, $createdA] = $this->service()->createPending($dest, Backup::TRIGGER_SCHEDULED, null, 'daily:2026-01-01T02:00');
        [$b, $createdB] = $this->service()->createPending($dest, Backup::TRIGGER_SCHEDULED, null, 'daily:2026-01-01T02:00');

        $this->assertTrue($createdA);
        $this->assertFalse($createdB);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, Backup::count());
    }

    public function test_run_is_idempotent_for_finished_backups(): void
    {
        Queue::fake();
        $this->settings(['enabled' => true]);
        $dest = $this->localDestination();

        [$backup] = $this->service()->createPending($dest, Backup::TRIGGER_MANUAL, 1);
        $this->service()->run($backup);
        $this->service()->run($backup);   // duplicate job delivery

        $this->assertCount(1, glob($this->tmp . '/dest/inventory_backup_*.sql.gz'));
        $this->assertSame(1, $backup->fresh()->attempts);
    }

    public function test_encrypted_backup_round_trip_and_wrong_password(): void
    {
        if (! BackupEncryptor::available()) {
            $this->markTestSkipped('sodium not available');
        }
        Queue::fake();
        $this->settings(['enabled' => true, 'encryption_enabled' => true, 'encryption_password' => 'correct horse battery']);
        $dest = $this->localDestination();

        [$backup] = $this->service()->createPending($dest, Backup::TRIGGER_MANUAL, 1);
        $backup = $this->service()->run($backup);

        $this->assertSame(Backup::STATUS_SUCCESSFUL, $backup->status);
        $this->assertTrue($backup->encrypted);
        $file = $this->tmp . '/dest/' . $backup->filename;
        $this->assertTrue(BackupEncryptor::isEncrypted($file));

        $enc = new BackupEncryptor();
        $out = $this->tmp . '/decrypted.sql.gz';
        $enc->decryptFile($file, $out, 'correct horse battery');
        $this->assertStringContainsString('CREATE TABLE', gzdecode(file_get_contents($out)));

        $this->expectException(\Throwable::class);
        $enc->decryptFile($file, $this->tmp . '/nope.sql.gz', 'wrong password');
    }

    public function test_retention_keeps_newest_and_deletes_old(): void
    {
        Queue::fake();
        $this->settings(['enabled' => true]);
        $dest = $this->localDestination(['retention_keep_last' => 2]);

        $ids = [];
        foreach (range(1, 4) as $i) {
            [$b] = $this->service()->createPending($dest, Backup::TRIGGER_MANUAL, 1);
            $b = $this->service()->run($b);
            $b->forceFill(['created_at' => now()->subDays(10 - $i)])->save();   // ordering by age
            $ids[] = $b->id;
            usleep(1100000);   // distinct second-resolution filenames
        }

        $kept = Backup::where('status', Backup::STATUS_SUCCESSFUL)->pluck('id')->all();
        $this->assertCount(2, $kept);
        $this->assertContains($ids[3], $kept);   // newest is always kept
        $this->assertCount(2, glob($this->tmp . '/dest/inventory_backup_*.sql.gz'));
    }

    public function test_retention_never_deletes_the_only_backup(): void
    {
        Queue::fake();
        $this->settings(['enabled' => true]);
        $dest = $this->localDestination(['retention_keep_days' => 1]);

        [$b] = $this->service()->createPending($dest, Backup::TRIGGER_MANUAL, 1);
        $b = $this->service()->run($b);
        $b->forceFill(['created_at' => now()->subDays(60)])->save();

        $this->assertCount(0, app(RetentionService::class)->candidates($dest->fresh()));
    }

    public function test_google_credentials_can_be_saved_and_used_by_client(): void
    {
        $settings = $this->settings(['google_client_id' => 'custom-client-id', 'google_client_secret' => 'custom-client-secret']);
        $client = app(\App\Services\Backup\Drivers\GoogleDriveClient::class);

        $this->assertSame('custom-client-id', $client->clientId());
        $this->assertSame('custom-client-secret', $client->clientSecret());
        $this->assertTrue($client->isConfigured());

        $rawSecret = \Illuminate\Support\Facades\DB::table('backup_settings')->where('id', $settings->id)->value('google_client_secret');
        $this->assertNotSame('custom-client-secret', $rawSecret); // encrypted in DB
    }
}
