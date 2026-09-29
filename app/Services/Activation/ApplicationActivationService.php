<?php

namespace App\Services\Activation;

use App\Models\ApplicationLicense;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ApplicationActivationService
 *
 * This is the SINGLE SOURCE OF TRUTH for activation status.
 *
 * Architecture:
 *  - Reads signed activation.dat from protected storage (never public/)
 *  - Verifies Ed25519 signature using the PUBLIC key only
 *  - Verifies installation_id matches this installation
 *  - Detects database tampering (if DB expiry differs from signed activation)
 *  - The database is a CACHE; signed file is authoritative
 *
 * Security guarantee:
 *  - A customer editing the DB expires_at cannot extend the subscription
 *  - Only a new signed activation.dat (generated with the private key) can change expiry
 */
class ApplicationActivationService
{
    // Storage disk paths (relative to storage/app/)
    private const ACTIVATION_PATH = 'private/activation.dat';
    private const PUBLIC_KEY_PATH = 'keys/license_public.pem';
    private const INSTALL_ID_PATH = 'private/installation.id';

    private const EXPECTED_PRODUCT = 'pharmacy_inventory';
    private const CACHE_KEY        = 'app_activation_result';
    private const CACHE_TTL        = 300; // 5 minutes

    // ──────────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Get the full activation result (cached for 5 minutes).
     */
    public function getActivation(): ActivationResult
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->verifyAll());
    }

    /** Invalidate the cached activation result (call after installing a new activation). */
    public function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function isActivated(): bool
    {
        return $this->getActivation()->isFullyValid();
    }

    public function isValid(): bool
    {
        $a = $this->getActivation();
        return $a->signatureValid && $a->installationValid && $a->productValid && $a->activationFileExists;
    }

    public function isExpired(): bool
    {
        return $this->getActivation()->status === 'expired';
    }

    public function daysRemaining(): ?int
    {
        return $this->getActivation()->daysRemaining;
    }

    public function expiresAt(): ?Carbon
    {
        $payload = $this->getActivation()->payload;
        if (!$payload || !isset($payload['expires_at'])) {
            return null;
        }
        return Carbon::parse($payload['expires_at']);
    }

    public function installationId(): string
    {
        return $this->getOrCreateInstallationId();
    }

    public function status(): string
    {
        return $this->getActivation()->status;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Core verification pipeline
    // ──────────────────────────────────────────────────────────────────────────

    private function verifyAll(): ActivationResult
    {
        // 1. Check activation file exists
        if (!Storage::exists(self::ACTIVATION_PATH)) {
            return new ActivationResult(
                signatureValid:       false,
                installationValid:    false,
                productValid:         false,
                notExpired:           false,
                dbConsistent:         true,  // can't check without file
                activationFileExists: false,
                status:               'missing',
                message:              'Activation file not found.',
            );
        }

        // 2. Parse activation file
        $raw = Storage::get(self::ACTIVATION_PATH);
        $data = json_decode($raw, true);

        if (!$data || !isset($data['payload'], $data['signature'])) {
            return new ActivationResult(
                signatureValid:       false,
                installationValid:    false,
                productValid:         false,
                notExpired:           false,
                dbConsistent:         true,
                activationFileExists: true,
                status:               'invalid',
                message:              'Activation file is malformed.',
            );
        }

        $payload   = $data['payload'];
        $sigBase64 = $data['signature'];

        // 3. Verify cryptographic signature
        $sigValid = $this->verifySignature($payload, $sigBase64);
        if (!$sigValid) {
            return new ActivationResult(
                signatureValid:       false,
                installationValid:    false,
                productValid:         false,
                notExpired:           false,
                dbConsistent:         true,
                activationFileExists: true,
                status:               'invalid',
                payload:              $payload,
                message:              'Activation signature is invalid.',
            );
        }

        // 4. Verify product identifier
        $productValid = isset($payload['product']) && $payload['product'] === self::EXPECTED_PRODUCT;

        // 5. Verify installation ID
        $currentId       = $this->getOrCreateInstallationId();
        $installationValid = isset($payload['installation_id']) && $payload['installation_id'] === $currentId;

        // 6. Determine expiry from the SIGNED payload (not the database)
        $expiresAt    = isset($payload['expires_at']) ? Carbon::parse($payload['expires_at']) : null;
        $now          = Carbon::now('UTC');
        $notExpired   = $expiresAt !== null && $expiresAt->greaterThan($now);
        $daysRemaining = $expiresAt ? (int) max(0, $now->diffInDays($expiresAt, false)) : null;

        // 7. Check database consistency (detect manual tampering)
        $dbConsistent = $this->verifyDatabaseConsistency($payload);

        // 8. Determine overall status
        $status = $this->determineStatus(
            sigValid:          $sigValid,
            productValid:      $productValid,
            installationValid: $installationValid,
            notExpired:        $notExpired,
            dbConsistent:      $dbConsistent,
        );

        return new ActivationResult(
            signatureValid:       $sigValid,
            installationValid:    $installationValid,
            productValid:         $productValid,
            notExpired:           $notExpired,
            dbConsistent:         $dbConsistent,
            activationFileExists: true,
            status:               $status,
            payload:              $payload,
            daysRemaining:        $daysRemaining,
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Signature verification
    // ──────────────────────────────────────────────────────────────────────────

    public function verifySignature(array $payload, string $sigBase64): bool
    {
        try {
            if (!Storage::exists(self::PUBLIC_KEY_PATH)) {
                Log::error('[Activation] Public key not found at ' . self::PUBLIC_KEY_PATH);
                return false;
            }

            $pemContent = Storage::get(self::PUBLIC_KEY_PATH);
            $publicKey  = $this->parsePemPublicKey($pemContent);

            if ($publicKey === null) {
                Log::error('[Activation] Failed to parse public key PEM.');
                return false;
            }

            $signature = base64_decode($sigBase64, strict: true);
            if ($signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
                return false;
            }

            $canonical = $this->canonicalJson($payload);

            return sodium_crypto_sign_verify_detached($signature, $canonical, $publicKey);

        } catch (\Throwable $e) {
            Log::error('[Activation] Signature verification exception: ' . $e->getMessage());
            return false;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Database consistency check
    // ──────────────────────────────────────────────────────────────────────────

    public function verifyDatabaseConsistency(array $signedPayload): bool
    {
        try {
            $license = ApplicationLicense::first();
            if (!$license) {
                // No DB record yet — considered consistent (not yet synced)
                return true;
            }

            $signedExpiry = Carbon::parse($signedPayload['expires_at'])->startOfDay();
            $dbExpiry     = Carbon::parse($license->expires_at)->startOfDay();

            // If the DB date is LATER than the signed date, someone manually extended it
            if ($dbExpiry->greaterThan($signedExpiry)) {
                Log::warning('[Activation] DATABASE TAMPERING DETECTED: DB expiry='
                    . $dbExpiry->toDateString() . ' > Signed expiry=' . $signedExpiry->toDateString());
                return false;
            }

            // Also check activation_id consistency
            $signedActivationId = $signedPayload['activation_id'] ?? null;
            if ($signedActivationId && $license->activation_id && $license->activation_id !== $signedActivationId) {
                Log::warning('[Activation] Activation ID mismatch: DB=' . $license->activation_id
                    . ' Signed=' . $signedActivationId);
                return false;
            }

            return true;

        } catch (\Throwable $e) {
            Log::error('[Activation] DB consistency check exception: ' . $e->getMessage());
            // If we can't read the DB, default to consistent (activation file is still valid)
            return true;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Activation file installation
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Install a new activation file.
     * Validates format, signature, product, installation ID and dates.
     * Stores the file in protected storage and syncs the database cache.
     *
     * @throws \RuntimeException on any validation failure
     */
    public function installActivationFile(string $filePath): ActivationResult
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Activation file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $data    = json_decode($content, true);

        if (!$data || !isset($data['payload'], $data['signature'])) {
            throw new \RuntimeException('Activation file is malformed (invalid JSON or missing fields).');
        }

        $payload   = $data['payload'];
        $sigBase64 = $data['signature'];

        // Validate required fields
        $required = ['product', 'installation_id', 'activation_id', 'activated_at', 'expires_at', 'version'];
        foreach ($required as $field) {
            if (!isset($payload[$field])) {
                throw new \RuntimeException("Activation file missing required field: {$field}");
            }
        }

        // Verify signature
        if (!$this->verifySignature($payload, $sigBase64)) {
            throw new \RuntimeException('Activation file has an invalid cryptographic signature. File rejected.');
        }

        // Verify product
        if ($payload['product'] !== self::EXPECTED_PRODUCT) {
            throw new \RuntimeException(
                "Product mismatch. Expected: " . self::EXPECTED_PRODUCT . " Got: " . $payload['product']
            );
        }

        // Verify installation ID
        $currentId = $this->getOrCreateInstallationId();
        if ($payload['installation_id'] !== $currentId) {
            throw new \RuntimeException(
                "Installation ID mismatch. This activation is for: {$payload['installation_id']}. " .
                "This installation is: {$currentId}. Activation rejected."
            );
        }

        // Verify dates are parseable
        $activatedAt = Carbon::parse($payload['activated_at']);
        $expiresAt   = Carbon::parse($payload['expires_at']);

        if ($expiresAt->lte($activatedAt)) {
            throw new \RuntimeException('Activation file has invalid dates: expires_at is not after activated_at.');
        }

        // Store to protected location
        $dir = dirname(storage_path('app/' . self::ACTIVATION_PATH));
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        Storage::put(self::ACTIVATION_PATH, $content);

        // Sync database cache
        $status = $expiresAt->greaterThan(Carbon::now('UTC')) ? 'active' : 'expired';
        ApplicationLicense::updateOrCreate(
            ['installation_id' => $currentId],
            [
                'activation_id' => $payload['activation_id'],
                'product'       => $payload['product'],
                'activated_at'  => $activatedAt,
                'expires_at'    => $expiresAt,
                'status'        => $status,
            ]
        );

        // Invalidate cache
        $this->invalidateCache();

        return $this->getActivation();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Installation ID management
    // ──────────────────────────────────────────────────────────────────────────

    public function getOrCreateInstallationId(): string
    {
        // Check protected storage first
        if (Storage::exists(self::INSTALL_ID_PATH)) {
            $id = trim(Storage::get(self::INSTALL_ID_PATH));
            if ($this->isValidInstallationId($id)) {
                return $id;
            }
        }

        // Generate a new installation ID
        $id = $this->generateInstallationId();

        $dir = dirname(storage_path('app/' . self::INSTALL_ID_PATH));
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        Storage::put(self::INSTALL_ID_PATH, $id);

        return $id;
    }

    private function generateInstallationId(): string
    {
        // PHARM- prefix + 8 random uppercase hex chars
        return 'PHARM-' . strtoupper(bin2hex(random_bytes(4)));
    }

    private function isValidInstallationId(string $id): bool
    {
        return (bool) preg_match('/^PHARM-[A-F0-9]{8}$/', $id);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Utilities
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Parse Ed25519 public key from SubjectPublicKeyInfo PEM.
     * Returns raw 32-byte public key or null on failure.
     */
    private function parsePemPublicKey(string $pem): ?string
    {
        // Strip PEM headers and decode
        $pem     = preg_replace('/-----[^-]+-----/', '', $pem);
        $pem     = preg_replace('/\s+/', '', $pem);
        $der     = base64_decode($pem, strict: true);

        if ($der === false || strlen($der) < 12) {
            return null;
        }

        // Ed25519 SubjectPublicKeyInfo DER prefix is 12 bytes: 302a300506032b6570032100
        // The public key is the last 32 bytes
        $prefix = hex2bin('302a300506032b6570032100');
        if (!str_starts_with($der, $prefix)) {
            return null;
        }

        $key = substr($der, strlen($prefix));
        return strlen($key) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES ? $key : null;
    }

    /**
     * Produce canonical JSON: sorted keys, compact, no unicode escaping.
     * Must match the generator exactly.
     */
    private function canonicalJson(array $data): string
    {
        ksort($data);
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Determine overall status string from individual check results.
     */
    private function determineStatus(
        bool $sigValid,
        bool $productValid,
        bool $installationValid,
        bool $notExpired,
        bool $dbConsistent,
    ): string {
        if (!$sigValid || !$productValid || !$installationValid) {
            return 'invalid';
        }
        if (!$dbConsistent) {
            return 'tampered';
        }
        if (!$notExpired) {
            return 'expired';
        }
        return 'active';
    }
}
