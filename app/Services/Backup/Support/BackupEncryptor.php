<?php

namespace App\Services\Backup\Support;

use App\Services\Backup\Exceptions\BackupException;
use App\Services\Backup\Exceptions\ConfigurationException;

/**
 * Streaming authenticated encryption for backup files (libsodium secretstream,
 * XChaCha20-Poly1305). Key = Argon2id(password, random per-file salt).
 *
 * File layout:
 *   "IBKENC1\n" | salt(16) | stream header(24) | { len(4, big-endian) | ciphertext }...
 *
 * Every chunk is authenticated and the last one carries the FINAL tag, so wrong
 * passwords, corruption and truncation are all detected.
 */
class BackupEncryptor
{
    public const MAGIC = "IBKENC1\n";
    public const PLAIN_CHUNK = 1048576; // 1 MiB

    public static function available(): bool
    {
        return extension_loaded('sodium') && function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push');
    }

    public static function isEncrypted(string $path): bool
    {
        $h = @fopen($path, 'rb');
        if (! $h) {
            return false;
        }
        $magic = fread($h, strlen(self::MAGIC));
        fclose($h);

        return $magic === self::MAGIC;
    }

    /** Short, non-reversible fingerprint used to detect a changed password. */
    public static function fingerprint(string $password): string
    {
        return substr(hash_hmac('sha256', $password, 'inventory-backup-fingerprint'), 0, 16);
    }

    public function encryptFile(string $source, string $target, string $password): void
    {
        $this->assertAvailable();

        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $key = $this->deriveKey($password, $salt);

        [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);
        sodium_memzero($key);

        $in = fopen($source, 'rb');
        $out = fopen($target, 'wb');
        if (! $in || ! $out) {
            throw new BackupException('Could not open files for encryption.', 'encryption_failed', true);
        }

        try {
            fwrite($out, self::MAGIC . $salt . $header);

            $next = fread($in, self::PLAIN_CHUNK);
            while (true) {
                $chunk = $next;
                $next = feof($in) ? '' : fread($in, self::PLAIN_CHUNK);
                $final = ($next === '' || $next === false);

                $cipher = sodium_crypto_secretstream_xchacha20poly1305_push(
                    $state,
                    $chunk === false ? '' : $chunk,
                    '',
                    $final ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE
                );

                if (fwrite($out, pack('N', strlen($cipher)) . $cipher) === false) {
                    throw new BackupException('Writing the encrypted file failed (disk full?).', 'encryption_failed', true);
                }
                if ($final) {
                    break;
                }
            }
        } finally {
            fclose($in);
            fclose($out);
        }
    }

    /**
     * Decrypt $source into $target. Throws on a wrong password or a corrupt/truncated file.
     * Pass $target = null to only verify (nothing is written).
     */
    public function decryptFile(string $source, ?string $target, string $password): void
    {
        $this->assertAvailable();

        $in = fopen($source, 'rb');
        if (! $in) {
            throw new BackupException('Could not open the encrypted file.', 'decryption_failed', false);
        }
        $out = $target === null ? null : fopen($target, 'wb');

        try {
            if (fread($in, strlen(self::MAGIC)) !== self::MAGIC) {
                throw new BackupException('This file is not an encrypted backup.', 'decryption_failed', false);
            }

            $salt = fread($in, SODIUM_CRYPTO_PWHASH_SALTBYTES);
            $header = fread($in, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
            if (strlen($salt) !== SODIUM_CRYPTO_PWHASH_SALTBYTES || strlen($header) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES) {
                throw new BackupException('Encrypted backup header is corrupt.', 'decryption_failed', false);
            }

            $key = $this->deriveKey($password, $salt);
            $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key);
            sodium_memzero($key);

            $sawFinal = false;
            while (! feof($in)) {
                $lenBytes = fread($in, 4);
                if ($lenBytes === '' || $lenBytes === false) {
                    break;
                }
                if (strlen($lenBytes) !== 4) {
                    throw new BackupException('Encrypted backup is truncated.', 'decryption_failed', false);
                }
                $len = unpack('N', $lenBytes)[1];
                if ($len > self::PLAIN_CHUNK + 64) {
                    throw new BackupException('Encrypted backup is corrupt.', 'decryption_failed', false);
                }

                $cipher = '';
                while (strlen($cipher) < $len && ! feof($in)) {
                    $cipher .= fread($in, $len - strlen($cipher));
                }
                if (strlen($cipher) !== $len) {
                    throw new BackupException('Encrypted backup is truncated.', 'decryption_failed', false);
                }

                $result = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $cipher);
                if ($result === false) {
                    throw new BackupException('Decryption failed: wrong password or corrupted backup.', 'decryption_failed', false);
                }

                [$plain, $tag] = $result;
                $out && fwrite($out, $plain);

                if ($tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
                    $sawFinal = true;
                    break;
                }
            }

            if (! $sawFinal) {
                throw new BackupException('Encrypted backup is truncated (missing end marker).', 'decryption_failed', false);
            }
        } finally {
            fclose($in);
            $out && fclose($out);
        }
    }

    protected function deriveKey(string $password, string $salt): string
    {
        return sodium_crypto_pwhash(
            SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES,
            $password,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
        );
    }

    protected function assertAvailable(): void
    {
        if (! self::available()) {
            throw new ConfigurationException('Backup encryption requires the PHP "sodium" extension, which is not enabled on this server.');
        }
    }
}
