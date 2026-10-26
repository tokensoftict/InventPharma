<?php

namespace App\Services\Backup\Contracts;

interface DatabaseDumper
{
    /** Short tool name recorded in the backup metadata. */
    public function name(): string;

    /**
     * Stream a complete logical dump into a gzip file.
     *
     * @param callable|null $heartbeat called periodically so the runner can prove it is alive
     * @return array<string,mixed> metadata (database, tables, rows, tool, ...)
     */
    public function dumpToGzip(string $gzPath, ?callable $heartbeat = null): array;
}
