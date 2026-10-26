<?php

namespace App\Services\Backup\Dump;

use App\Services\Backup\Contracts\DatabaseDumper;
use App\Services\Backup\Exceptions\BackupException;
use Symfony\Component\Process\Process;

/**
 * Dump using the mysqldump binary, streamed straight into gzip (no uncompressed
 * file ever touches the disk). Credentials are passed through a temporary
 * 0600 defaults-file, never on the command line (visible in `ps`).
 */
class MysqldumpDumper implements DatabaseDumper
{
    public function __construct(protected DatabaseDumpService $service, protected string $binary)
    {
    }

    public function name(): string
    {
        return 'mysqldump';
    }

    /** Locate a working mysqldump binary or return null. */
    public static function locate(): ?string
    {
        $candidates = array_filter([
            config('backup-manager.mysqldump_path'),
            'mysqldump',
            'mariadb-dump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
            '/opt/homebrew/opt/mysql-client/bin/mysqldump',
            '/Applications/XAMPP/xamppfiles/bin/mysqldump',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\mysql-8.0\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0\\bin\\mysqldump.exe',
        ]);

        foreach ($candidates as $candidate) {
            try {
                $probe = new Process([$candidate, '--version']);
                $probe->setTimeout(10);
                $probe->run();
                if ($probe->isSuccessful()) {
                    return $candidate;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    public function dumpToGzip(string $gzPath, ?callable $heartbeat = null): array
    {
        $config = $this->service->connectionConfig();
        $database = (string) $config['database'];

        $defaults = tempnam(sys_get_temp_dir(), 'bkp');
        chmod($defaults, 0600);
        file_put_contents($defaults, $this->defaultsFile($config));

        $command = [
            $this->binary,
            '--defaults-extra-file=' . $defaults,   // must be the first option
            '--single-transaction', '--quick', '--routines', '--triggers', '--events',
            '--hex-blob', '--skip-lock-tables', '--no-tablespaces',
            '--default-character-set=utf8mb4',
        ];

        if ($this->supports('set-gtid-purged')) {
            $command[] = '--set-gtid-purged=OFF';
        }

        foreach ($this->service->excludedTables() as $table) {
            $command[] = '--ignore-table=' . $database . '.' . $table;
        }

        $command[] = $database;

        $errFile = tempnam(sys_get_temp_dir(), 'bkpe');
        $gz = gzopen($gzPath, 'wb6');
        if (! $gz) {
            @unlink($defaults);
            @unlink($errFile);
            throw new BackupException('Cannot create the dump file (permission denied or disk full).', 'dump_failed', true);
        }

        $tail = '';
        try {
            $process = new Process($command);
            $process->setTimeout(null);
            $process->setIdleTimeout(900); // 15 min without output = hung server
            $process->start();

            foreach ($process as $type => $data) {
                if ($type === Process::OUT) {
                    if (gzwrite($gz, $data) === false) {
                        $process->stop(0);
                        throw new BackupException('Writing the dump failed (disk full?).', 'dump_failed', true);
                    }
                    $tail = substr($tail . $data, -512);
                    $heartbeat && $heartbeat();
                } else {
                    file_put_contents($errFile, $data, FILE_APPEND);
                }
            }

            if (! $process->isSuccessful()) {
                $stderr = trim((string) file_get_contents($errFile));
                throw new BackupException('mysqldump failed: ' . $this->clean($stderr ?: $process->getErrorOutput() ?: 'exit code ' . $process->getExitCode()), 'dump_failed', true);
            }
        } finally {
            gzclose($gz);
            @unlink($defaults);
            @unlink($errFile);
        }

        if (! str_contains($tail, 'Dump completed')) {
            throw new BackupException('mysqldump output looks truncated (no completion marker).', 'dump_failed', true);
        }

        return ['tool' => 'mysqldump', 'database' => $database, 'tables' => $this->tableCount()];
    }

    protected function tableCount(): ?int
    {
        try {
            $ex = $this->service->excludedTables();
            $db = \Illuminate\Support\Facades\DB::connection(config('backup-manager.database_connection'));
            $names = collect($db->select('SELECT table_name AS n FROM information_schema.tables WHERE table_schema = ? AND table_type = "BASE TABLE"', [$this->service->databaseName()]))
                ->pluck('n')->map(fn ($n) => (string) $n);

            return $names->reject(fn ($n) => in_array($n, $ex, true))->count();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function supports(string $option): bool
    {
        try {
            $help = new Process([$this->binary, '--help']);
            $help->setTimeout(15);
            $help->run();

            return str_contains($help->getOutput(), $option);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function defaultsFile(array $config): string
    {
        $esc = fn ($v) => '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $v) . '"';
        $lines = ['[client]', 'user=' . $esc($config['username'] ?? ''), 'password=' . $esc($config['password'] ?? '')];

        if (! empty($config['unix_socket'])) {
            $lines[] = 'socket=' . $esc($config['unix_socket']);
        } else {
            $lines[] = 'host=' . $esc($config['host'] ?? '127.0.0.1');
            $lines[] = 'port=' . (int) ($config['port'] ?? 3306);
        }

        return implode("\n", $lines) . "\n";
    }

    /** Strip anything that could carry credentials out of tool output. */
    protected function clean(string $text): string
    {
        $password = (string) ($this->service->connectionConfig()['password'] ?? '');
        if ($password !== '') {
            $text = str_replace($password, '***', $text);
        }

        return mb_substr(preg_replace('/\s+/', ' ', $text), 0, 400);
    }
}
