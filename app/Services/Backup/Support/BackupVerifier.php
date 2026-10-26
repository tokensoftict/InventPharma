<?php

namespace App\Services\Backup\Support;

use App\Services\Backup\Exceptions\VerificationFailedException;

/** Local verification of a (gzipped) SQL dump. */
class BackupVerifier
{
    /**
     * Confirm the file exists, is non-empty, is a readable gzip stream end to end
     * (CRC checked by zlib at EOF) and looks like a complete database dump.
     *
     * @return array{uncompressed_bytes:int, has_schema:bool, has_completion_marker:bool}
     */
    public function verifyGzipDump(string $path, ?string $expectedDatabase = null): array
    {
        if (! is_file($path)) {
            throw new VerificationFailedException('Backup file does not exist.');
        }
        if (filesize($path) <= 0) {
            throw new VerificationFailedException('Backup file is empty.');
        }

        $gz = @gzopen($path, 'rb');
        if (! $gz) {
            throw new VerificationFailedException('Backup file cannot be opened as gzip.');
        }

        $bytes = 0;
        $head = '';
        $tail = '';
        $schema = false;

        while (! gzeof($gz)) {
            $chunk = gzread($gz, 262144);
            if ($chunk === false) {
                gzclose($gz);
                throw new VerificationFailedException('Backup file is corrupt (gzip read error).');
            }
            $bytes += strlen($chunk);
            if (strlen($head) < 262144) {
                $head .= $chunk;
            }
            if (! $schema && stripos($chunk, 'CREATE TABLE') !== false) {
                $schema = true;
            }
            $tail = substr($tail . $chunk, -1024);
        }
        gzclose($gz);

        if ($bytes === 0) {
            throw new VerificationFailedException('Backup contains no data.');
        }
        if (! $schema && ! str_contains($head, 'CREATE TABLE')) {
            throw new VerificationFailedException('Backup does not contain any table definitions.');
        }

        $complete = str_contains($tail, 'Dump completed');
        if (! $complete) {
            throw new VerificationFailedException('Backup is incomplete (missing completion marker).');
        }

        if ($expectedDatabase && stripos($head, $expectedDatabase) === false) {
            throw new VerificationFailedException('Backup does not reference the expected database.');
        }

        return ['uncompressed_bytes' => $bytes, 'has_schema' => true, 'has_completion_marker' => true];
    }
}
