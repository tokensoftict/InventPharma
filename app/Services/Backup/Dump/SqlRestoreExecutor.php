<?php

namespace App\Services\Backup\Dump;

use App\Services\Backup\Exceptions\BackupException;
use PDO;

/**
 * Streams a (gzipped) SQL dump into the database, statement by statement.
 * Understands quotes, comments and mysqldump's DELIMITER directives. Works for
 * dumps produced by both PhpDumper and mysqldump.
 *
 * CREATE VIEW statements that fail because a table/view they depend on has not
 * been created yet are retried after everything else.
 */
class SqlRestoreExecutor
{
    public function __construct(protected PDO $pdo)
    {
    }

    /** @return int number of statements executed */
    public function executeGzip(string $gzPath, ?callable $heartbeat = null): int
    {
        $gz = gzopen($gzPath, 'rb');
        if (! $gz) {
            throw new BackupException('Cannot open the backup file for restore.', 'restore_failed', false);
        }

        $count = 0;
        $deferredViews = [];

        try {
            foreach ($this->statements($gz) as $sql) {
                if ($this->isOnlyComment($sql)) {
                    continue;
                }
                $sql = $this->prepare($sql);

                try {
                    $this->pdo->exec($sql);
                    $count++;
                } catch (\PDOException $e) {
                    if ($this->isView($sql) && in_array((int) ($e->errorInfo[1] ?? 0), [1146, 1356], true)) {
                        $deferredViews[] = $sql;
                        continue;
                    }
                    throw new BackupException('Restore failed on statement ' . ($count + 1) . ': ' . mb_substr($e->getMessage(), 0, 300), 'restore_failed', false, $e);
                }

                if ($heartbeat && $count % 200 === 0) {
                    $heartbeat();
                }
            }
        } finally {
            gzclose($gz);
        }

        // retry deferred views until no progress
        for ($pass = 0; $deferredViews && $pass < 5; $pass++) {
            $still = [];
            foreach ($deferredViews as $sql) {
                try {
                    $this->pdo->exec($sql);
                    $count++;
                } catch (\PDOException $e) {
                    $still[] = $sql;
                }
            }
            if (count($still) === count($deferredViews)) {
                throw new BackupException('Restore could not recreate ' . count($still) . ' view(s) due to missing dependencies.', 'restore_failed', false);
            }
            $deferredViews = $still;
        }

        return $count;
    }

    /** @param resource $stream  @return \Generator<string> */
    public function statements($stream): \Generator
    {
        $delimiter = ';';
        $buffer = '';
        $quote = null;        // ' " `
        $lineComment = false;
        $blockComment = false;
        $carry = '';

        $last = false;

        while (! $last) {
            $chunk = gzeof($stream) ? '' : gzread($stream, 65536);
            $last = ($chunk === false || $chunk === '');
            $data = $carry . ($last ? '' : $chunk);
            $carry = '';
            $len = strlen($data);

            for ($i = 0; $i < $len; $i++) {
                $c = $data[$i];

                // Keep a short tail for the next chunk so multi-character tokens
                // (comments, DELIMITER, custom delimiters, escapes) never straddle a boundary.
                if (! $last && $i >= $len - 16) {
                    $carry = substr($data, $i);
                    break;
                }

                if ($lineComment) {
                    $buffer .= $c;
                    if ($c === "\n") {
                        $lineComment = false;
                    }
                    continue;
                }
                if ($blockComment) {
                    $buffer .= $c;
                    if ($c === '*' && ($data[$i + 1] ?? '') === '/') {
                        $buffer .= '/';
                        $i++;
                        $blockComment = false;
                    }
                    continue;
                }
                if ($quote) {
                    $buffer .= $c;
                    if ($c === '\\' && $quote !== '`') {
                        $buffer .= $data[$i + 1] ?? '';
                        $i++;
                    } elseif ($c === $quote) {
                        if (($data[$i + 1] ?? '') === $quote) {
                            $buffer .= $quote;
                            $i++;
                        } else {
                            $quote = null;
                        }
                    }
                    continue;
                }

                if ($c === '\'' || $c === '"' || $c === '`') {
                    $quote = $c;
                    $buffer .= $c;
                    continue;
                }
                if ($c === '#' || ($c === '-' && ($data[$i + 1] ?? '') === '-' && in_array($data[$i + 2] ?? ' ', [' ', "\t", "\n", "\r"], true))) {
                    $lineComment = true;
                    $buffer .= $c;
                    continue;
                }
                if ($c === '/' && ($data[$i + 1] ?? '') === '*') {
                    $blockComment = true;
                    $buffer .= $c;
                    continue;
                }

                // DELIMITER directive (only valid at the start of a line)
                if (($c === 'D' || $c === 'd') && trim($buffer) === '' && strncasecmp(substr($data, $i, 10), 'DELIMITER ', 10) === 0) {
                    $end = strpos($data, "\n", $i);
                    if ($end === false) {
                        $carry = substr($data, $i);
                        break;
                    }
                    $delimiter = trim(substr($data, $i + 10, $end - $i - 10));
                    $buffer = '';
                    $i = $end;
                    continue;
                }

                if ($c === $delimiter[0] && substr($data, $i, strlen($delimiter)) === $delimiter) {
                    $statement = trim($buffer);
                    $buffer = '';
                    $i += strlen($delimiter) - 1;
                    if ($statement !== '') {
                        yield $statement;
                    }
                    continue;
                }

                $buffer .= $c;
            }
        }

        if (trim($buffer) !== '' && ! $this->isOnlyComment($buffer)) {
            yield trim($buffer);
        }
    }

    protected function prepare(string $sql): string
    {
        // Strip DEFINER=user@host from DDL so restores work on a server without that account.
        // Never touch data statements: their text could legitimately contain "DEFINER=".
        if (! preg_match('/^\s*(INSERT|REPLACE)\b/i', $sql)) {
            $sql = preg_replace('/\sDEFINER\s*=\s*(`[^`]*`|[^@\s]+)@(`[^`]*`|[^\s]+)/i', '', $sql, 1);
        }

        return $sql;
    }

    protected function isView(string $sql): bool
    {
        return (bool) preg_match('/^\s*CREATE\s+(OR\s+REPLACE\s+)?(ALGORITHM\s*=\s*\w+\s+)?(DEFINER\s*=\s*\S+\s+)?(SQL\s+SECURITY\s+\w+\s+)?VIEW\b/i', $sql);
    }

    protected function isOnlyComment(string $sql): bool
    {
        $stripped = preg_replace(['~/\*(?!\!).*?\*/~s', '~^\s*(--|#)[^\n]*$~m'], '', $sql);

        return trim((string) $stripped) === '';
    }
}
