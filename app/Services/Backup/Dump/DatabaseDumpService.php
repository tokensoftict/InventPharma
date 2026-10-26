<?php

namespace App\Services\Backup\Dump;

use App\Services\Backup\Contracts\DatabaseDumper;
use App\Services\Backup\Exceptions\BackupException;
use App\Services\Backup\Exceptions\ConfigurationException;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Chooses the dump strategy and exposes shared helpers (dedicated connection,
 * size estimate). Dedicated connection = the dump never alters the session state
 * (time zone, transaction) of the connection the application itself uses.
 */
class DatabaseDumpService
{
    public function dumper(): DatabaseDumper
    {
        $mode = config('backup-manager.dump_driver', 'auto');

        if ($mode === 'php') {
            return new PhpDumper($this);
        }

        $binary = MysqldumpDumper::locate();

        if ($mode === 'mysqldump') {
            if (! $binary) {
                throw new ConfigurationException('mysqldump was not found. Set BACKUP_MYSQLDUMP_PATH or use BACKUP_DUMP_DRIVER=php.');
            }

            return new MysqldumpDumper($this, $binary);
        }

        return $binary ? new MysqldumpDumper($this, $binary) : new PhpDumper($this);
    }

    /** @return array<string,mixed> */
    public function connectionConfig(): array
    {
        $name = config('backup-manager.database_connection') ?: config('database.default');
        $config = config("database.connections.{$name}");

        if (! $config || ! in_array($config['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            throw new ConfigurationException('The Backup Manager supports MySQL/MariaDB databases only.');
        }

        return $config;
    }

    /** A private connection used exclusively by the backup/restore machinery. */
    public function dedicatedConnection(string $alias = 'backup_dump'): Connection
    {
        return DB::connectUsing($alias, $this->connectionConfig(), true);
    }

    public function databaseName(): string
    {
        return (string) $this->connectionConfig()['database'];
    }

    /** @return string[] */
    public function excludedTables(): array
    {
        return array_values(array_unique(config('backup-manager.excluded_tables', [])));
    }

    /** Upper-bound estimate of the uncompressed data size in bytes. */
    public function estimateBytes(): int
    {
        try {
            $row = DB::connection(config('backup-manager.database_connection'))->selectOne(
                'SELECT COALESCE(SUM(data_length + index_length), 0) AS bytes FROM information_schema.tables WHERE table_schema = ?',
                [$this->databaseName()]
            );

            return (int) ($row->bytes ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    public function fail(string $message, ?\Throwable $e = null): never
    {
        throw new BackupException($message, 'dump_failed', true, $e);
    }
}
