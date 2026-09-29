<?php

namespace App\Console\Commands;

use App\Services\Activation\ApplicationActivationService;
use Illuminate\Console\Command;

/**
 * Display the current application activation status.
 *
 * Usage:
 *   php artisan app:status
 */
class ApplicationStatus extends Command
{
    protected $signature   = 'app:status';
    protected $description = 'Display the current application activation and subscription status.';

    public function __construct(
        private readonly ApplicationActivationService $activationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $result  = $this->activationService->getActivation();
        $payload = $result->payload ?? [];

        $this->info('');
        $this->line('  Application Activation Status');
        $this->line('  ─────────────────────────────');
        $this->info('');

        $product      = $payload['product'] ?? 'Pharmacy Inventory';
        $installId    = $this->activationService->installationId();
        $activationId = $payload['activation_id'] ?? 'N/A';

        $statusLabel = match ($result->status) {
            'active'   => '<fg=green>ACTIVE</>',
            'expired'  => '<fg=yellow>EXPIRED</>',
            'missing'  => '<fg=red>NOT ACTIVATED</>',
            'invalid'  => '<fg=red>INVALID</>',
            'tampered' => '<fg=red>TAMPERED — INVALID</>',
            default    => '<fg=red>UNKNOWN</>',
        };

        $this->line('  Product:        ' . ucwords(str_replace('_', ' ', $product)));
        $this->line('  Installation:   ' . $installId);
        $this->line('  Activation ID:  ' . $activationId);
        $this->line('  Status:         ' . $statusLabel);
        $this->info('');

        if (!empty($payload['activated_at'])) {
            $this->line('  Activated:      ' . \Carbon\Carbon::parse($payload['activated_at'])->format('d F Y'));
        }
        if (!empty($payload['expires_at'])) {
            $this->line('  Expires:        ' . \Carbon\Carbon::parse($payload['expires_at'])->format('d F Y'));
        }
        if ($result->daysRemaining !== null) {
            $days  = $result->daysRemaining;
            $color = $days > 30 ? 'green' : ($days > 7 ? 'yellow' : 'red');
            $this->line("  Days Remaining: <fg={$color}>{$days}</>");
        }

        $this->info('');
        $this->line('  Security Checks:');
        $this->line('  ─────────────────────────────');
        $this->printCheck('  Signature',            $result->signatureValid);
        $this->printCheck('  Installation Match',   $result->installationValid);
        $this->printCheck('  Product Valid',        $result->productValid);
        $this->printCheck('  Not Expired',          $result->notExpired);
        $this->printCheck('  Database Consistent',  $result->dbConsistent);
        $this->info('');

        return self::SUCCESS;
    }

    private function printCheck(string $label, bool $pass): void
    {
        $icon  = $pass ? '<fg=green>✓</>' : '<fg=red>✗</>';
        $value = $pass ? '<fg=green>VALID</>' : '<fg=red>INVALID</>';
        $this->line("{$label}: {$icon} {$value}");
    }
}
