<?php

namespace App\Services\Activation;

/**
 * Immutable value object representing the result of an activation verification.
 */
final class ActivationResult
{
    public function __construct(
        public readonly bool    $signatureValid,
        public readonly bool    $installationValid,
        public readonly bool    $productValid,
        public readonly bool    $notExpired,
        public readonly bool    $dbConsistent,
        public readonly bool    $activationFileExists,
        public readonly string  $status,          // 'active' | 'expired' | 'missing' | 'invalid' | 'tampered'
        public readonly ?array  $payload = null,
        public readonly ?string $message = null,
        public readonly ?int    $daysRemaining = null,
    ) {}

    public function isFullyValid(): bool
    {
        return $this->signatureValid
            && $this->installationValid
            && $this->productValid
            && $this->notExpired
            && $this->dbConsistent
            && $this->activationFileExists;
    }

    public function isLocked(): bool
    {
        // Tampered / invalid signature / mismatched installation = hard lock
        return in_array($this->status, ['invalid', 'tampered'], true);
    }
}
