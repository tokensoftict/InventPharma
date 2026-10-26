<?php

namespace Tests\Feature\Backup;

use App\Models\BackupDestination;
use App\Models\BackupSetting;
use App\Services\Backup\Contracts\DatabaseDumper;
use App\Services\Backup\Dump\DatabaseDumpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Backup Manager tests run against a private in-memory SQLite database that only contains
 * the four backup_* tables. They never touch the application's real database.
 */
abstract class BackupTestCase extends TestCase
{
    protected string $tmp;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.backup_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false],
            'database.default' => 'backup_test',
            'cache.default' => 'array',
            'queue.default' => 'sync',
        ]);
        DB::purge('backup_test');
        Cache::flush();

        (include base_path('database/migrations/2026_10_26_160000_create_backup_manager_tables.php'))->up();
        (include base_path('database/migrations/2026_10_26_170000_add_google_credentials_to_backup_settings_table.php'))->up();

        $this->tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'backup-test-' . bin2hex(random_bytes(4));
        File::makeDirectory($this->tmp . '/dest', 0777, true);
        config(['backup-manager.staging_path' => $this->tmp . '/stage', 'backup-manager.excluded_tables' => []]);

        $this->app->instance(DatabaseDumpService::class, new FakeDumpService(new FakeDumper()));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);
        parent::tearDown();
    }

    protected function localDestination(array $attrs = []): BackupDestination
    {
        $d = new BackupDestination(array_merge([
            'name' => 'Test local', 'type' => BackupDestination::TYPE_LOCAL, 'enabled' => true,
        ], $attrs));
        $d->setConf(['path' => $this->tmp . '/dest']);
        $d->save();

        app(\App\Services\Backup\Drivers\LocalDriver::class)->initialize($d);
        $d->markStatus(BackupDestination::STATUS_READY);

        return $d->fresh();
    }

    protected function settings(array $attrs = []): BackupSetting
    {
        $s = BackupSetting::current();
        $s->fill($attrs)->save();

        return $s->fresh();
    }
}

class FakeDumper implements DatabaseDumper
{
    public bool $fail = false;

    public function name(): string
    {
        return 'fake';
    }

    public function dumpToGzip(string $gzPath, ?callable $heartbeat = null): array
    {
        if ($this->fail) {
            throw new \RuntimeException('dump exploded');
        }
        $sql = "-- fake dump of `inventory_test`\nCREATE TABLE `t` (`id` int);\nINSERT INTO `t` VALUES (1);\n-- Dump completed on 2026-01-01\n";
        file_put_contents($gzPath, gzencode($sql));

        return ['tool' => 'fake', 'database' => 'inventory_test', 'tables' => 1];
    }
}

class FakeDumpService extends DatabaseDumpService
{
    public function __construct(public FakeDumper $fake)
    {
    }

    public function dumper(): DatabaseDumper
    {
        return $this->fake;
    }

    public function estimateBytes(): int
    {
        return 1024;
    }

    public function databaseName(): string
    {
        return 'inventory_test';
    }
}
