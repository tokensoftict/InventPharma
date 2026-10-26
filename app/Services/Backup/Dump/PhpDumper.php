<?php

namespace App\Services\Backup\Dump;

use App\Services\Backup\Contracts\DatabaseDumper;
use App\Services\Backup\Exceptions\BackupException;
use PDO;

/**
 * Pure-PHP logical dump. Produces a mysqldump-compatible script so it can be
 * restored by either the built-in executor or the mysql client.
 *
 * - Runs inside a REPEATABLE READ / CONSISTENT SNAPSHOT transaction on a private
 *   connection, so the dump is point-in-time consistent for InnoDB tables and does
 *   not lock tables or disturb the application's own connection.
 * - Rows are streamed (unbuffered) and written straight into gzip: memory use stays
 *   flat regardless of database size.
 */
class PhpDumper implements DatabaseDumper
{
    protected $gz;
    protected string $buffer = '';
    protected float $lastBeat = 0;

    public function __construct(protected DatabaseDumpService $service)
    {
    }

    public function name(): string
    {
        return 'php';
    }

    public function dumpToGzip(string $gzPath, ?callable $heartbeat = null): array
    {
        $database = $this->service->databaseName();
        $conn = $this->service->dedicatedConnection('backup_dump');
        $pdo = $conn->getPdo();
        $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true); // exact decimals/floats, no PHP float rounding

        $this->gz = gzopen($gzPath, 'wb6');
        if (! $this->gz) {
            throw new BackupException('Cannot create the dump file (permission denied or disk full).', 'dump_failed', true);
        }

        $meta = ['tool' => 'php', 'database' => $database, 'tables' => 0, 'views' => 0, 'rows' => []];

        try {
            $conn->statement("SET SESSION time_zone = '+00:00'");
            $conn->statement('SET SESSION wait_timeout = 28800');
            $conn->statement('SET SESSION net_write_timeout = 600');
            $conn->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $conn->statement('START TRANSACTION WITH CONSISTENT SNAPSHOT');

            $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();

            $this->write("-- Inventory Backup Manager dump (php)\n-- Database: {$database}\n-- Server version: {$version}\n-- Generated: " . gmdate('Y-m-d H:i:s') . " UTC\n\n");
            $this->write("/*!40101 SET NAMES utf8mb4 */;\nSET FOREIGN_KEY_CHECKS=0;\nSET UNIQUE_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET TIME_ZONE='+00:00';\n\n");

            [$tables, $views] = $this->objects($pdo);

            foreach ($tables as $table) {
                $this->dumpTable($pdo, $table, $meta, $heartbeat);
            }
            $meta['tables'] = count($tables);

            foreach ($views as $view) {
                $this->dumpView($pdo, $view);
            }
            $meta['views'] = count($views);

            $this->dumpRoutines($pdo, $database);
            $this->dumpTriggers($pdo);
            $this->dumpEvents($pdo);

            $this->write("\nSET FOREIGN_KEY_CHECKS=1;\nSET UNIQUE_CHECKS=1;\n-- Dump completed on " . gmdate('Y-m-d H:i:s') . "\n");
            $this->flush();
        } catch (\Throwable $e) {
            throw $e instanceof BackupException ? $e : new BackupException('Database dump failed: ' . $this->safeMessage($e), 'dump_failed', true, $e);
        } finally {
            try {
                $conn->statement('ROLLBACK');
            } catch (\Throwable) {
            }
            gzclose($this->gz);
            $this->gz = null;
            $conn->disconnect();
        }

        return $meta;
    }

    /** @return array{0:string[],1:string[]} base tables, views */
    protected function objects(PDO $pdo): array
    {
        $excluded = $this->service->excludedTables();
        $tables = $views = [];

        foreach ($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as [$name, $type]) {
            if (in_array($name, $excluded, true)) {
                continue;
            }
            if (strtoupper($type) === 'VIEW') {
                $views[] = $name;
            } else {
                $tables[] = $name;
            }
        }

        return [$tables, $views];
    }

    protected function dumpTable(PDO $pdo, string $table, array &$meta, ?callable $heartbeat): void
    {
        $q = $this->ident($table);

        $create = $pdo->query("SHOW CREATE TABLE {$q}")->fetch(PDO::FETCH_NUM)[1] ?? null;
        if (! $create) {
            throw new BackupException("Could not read the structure of table {$table}.", 'dump_failed', true);
        }

        $this->write("--\n-- Table: {$table}\n--\n\nDROP TABLE IF EXISTS {$q};\n{$create};\n\n");

        // Column list: skip generated columns (they cannot be inserted), hex-encode binary types.
        $columns = $pdo->query('SELECT column_name, data_type, extra FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ' . $pdo->quote($table) . ' ORDER BY ordinal_position')->fetchAll(PDO::FETCH_NUM);

        $select = [];
        $insertCols = [];
        $kinds = [];
        foreach ($columns as [$name, $type, $extra]) {
            if (stripos((string) $extra, 'GENERATED') !== false) {
                continue;
            }
            $c = $this->ident($name);
            $type = strtolower((string) $type);
            $insertCols[] = $c;

            if (in_array($type, ['binary', 'varbinary', 'tinyblob', 'blob', 'mediumblob', 'longblob', 'bit'], true)) {
                $select[] = "HEX({$c})";
                $kinds[] = 'hex';
            } elseif (in_array($type, ['geometry', 'point', 'linestring', 'polygon', 'multipoint', 'multilinestring', 'multipolygon', 'geometrycollection'], true)) {
                $select[] = "ST_AsText({$c})";
                $kinds[] = 'geom';
            } else {
                $select[] = $c;
                $kinds[] = 'plain';
            }
        }

        $meta['rows'][$table] = 0;
        if (! $insertCols) {
            return;
        }

        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false); // stream rows
        $stmt = $pdo->query('SELECT ' . implode(',', $select) . " FROM {$q}");

        $head = "INSERT INTO {$q} (" . implode(',', $insertCols) . ") VALUES\n";
        $batch = [];
        $batchBytes = 0;
        $count = 0;

        while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
            $values = [];
            foreach ($row as $i => $value) {
                $values[] = $this->literal($pdo, $value, $kinds[$i]);
            }
            $line = '(' . implode(',', $values) . ')';
            $batch[] = $line;
            $batchBytes += strlen($line);
            $count++;

            if ($batchBytes > 524288 || count($batch) >= 500) {
                $this->write($head . implode(",\n", $batch) . ";\n");
                $batch = [];
                $batchBytes = 0;
                $this->beat($heartbeat);
            }
        }
        $stmt->closeCursor();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        if ($batch) {
            $this->write($head . implode(",\n", $batch) . ";\n");
        }
        $this->write("\n");
        $meta['rows'][$table] = $count;
        $this->beat($heartbeat);
    }

    protected function dumpView(PDO $pdo, string $view): void
    {
        $q = $this->ident($view);
        $create = $pdo->query("SHOW CREATE VIEW {$q}")->fetch(PDO::FETCH_NUM)[1] ?? null;
        if ($create) {
            $this->write("--\n-- View: {$view}\n--\n\nDROP VIEW IF EXISTS {$q};\n" . $this->stripDefiner($create) . ";\n\n");
        }
    }

    protected function dumpRoutines(PDO $pdo, string $database): void
    {
        foreach (['FUNCTION' => 2, 'PROCEDURE' => 2] as $kind => $idx) {
            $rows = $pdo->query("SHOW {$kind} STATUS WHERE Db = " . $pdo->quote($database))->fetchAll(PDO::FETCH_NUM);
            foreach ($rows as $row) {
                $name = $row[1];
                $def = $pdo->query("SHOW CREATE {$kind} " . $this->ident($name))->fetch(PDO::FETCH_NUM)[$idx] ?? null;
                if ($def) {
                    $this->write("--\n-- {$kind}: {$name}\n--\n\nDROP {$kind} IF EXISTS " . $this->ident($name) . ";\nDELIMITER ;;\n" . $this->stripDefiner($def) . " ;;\nDELIMITER ;\n\n");
                }
            }
        }
    }

    protected function dumpTriggers(PDO $pdo): void
    {
        foreach ($pdo->query('SHOW TRIGGERS')->fetchAll(PDO::FETCH_NUM) as $row) {
            $name = $row[0];
            $def = $pdo->query('SHOW CREATE TRIGGER ' . $this->ident($name))->fetch(PDO::FETCH_NUM)[2] ?? null;
            if ($def) {
                $this->write("--\n-- Trigger: {$name}\n--\n\nDROP TRIGGER IF EXISTS " . $this->ident($name) . ";\nDELIMITER ;;\n" . $this->stripDefiner($def) . " ;;\nDELIMITER ;\n\n");
            }
        }
    }

    protected function dumpEvents(PDO $pdo): void
    {
        try {
            $events = $pdo->query('SHOW EVENTS')->fetchAll(PDO::FETCH_NUM);
        } catch (\Throwable) {
            return; // user may lack EVENT privilege
        }

        foreach ($events as $row) {
            $name = $row[1];
            $def = $pdo->query('SHOW CREATE EVENT ' . $this->ident($name))->fetch(PDO::FETCH_NUM)[3] ?? null;
            if ($def) {
                $this->write("--\n-- Event: {$name}\n--\n\nDROP EVENT IF EXISTS " . $this->ident($name) . ";\nDELIMITER ;;\n" . $this->stripDefiner($def) . " ;;\nDELIMITER ;\n\n");
            }
        }
    }

    protected function literal(PDO $pdo, mixed $value, string $kind): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if ($kind === 'hex') {
            return $value === '' ? "''" : '0x' . $value;
        }
        if ($kind === 'geom') {
            return 'ST_GeomFromText(' . $pdo->quote((string) $value) . ')';
        }

        return $pdo->quote((string) $value);
    }

    protected function stripDefiner(string $sql): string
    {
        return preg_replace('/\sDEFINER\s*=\s*(`[^`]*`|[^@\s]+)@(`[^`]*`|[^\s]+)/i', '', $sql, 1);
    }

    protected function ident(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    protected function write(string $data): void
    {
        $this->buffer .= $data;
        if (strlen($this->buffer) > 262144) {
            $this->flush();
        }
    }

    protected function flush(): void
    {
        if ($this->buffer !== '') {
            if (gzwrite($this->gz, $this->buffer) === false) {
                throw new BackupException('Writing the dump failed (disk full?).', 'dump_failed', true);
            }
            $this->buffer = '';
        }
    }

    protected function beat(?callable $heartbeat): void
    {
        if ($heartbeat && microtime(true) - $this->lastBeat >= 2) {
            $this->flush();
            $heartbeat();
            $this->lastBeat = microtime(true);
        }
    }

    protected function safeMessage(\Throwable $e): string
    {
        // Strip SQL text that might embed credentials/connection strings.
        return mb_substr(preg_replace('/\s+/', ' ', explode('(Connection:', $e->getMessage())[0]), 0, 400);
    }
}
