<?php

namespace App\Console\Commands;

use App\Services\Activation\ApplicationActivationService;
use Illuminate\Console\Command;

/**
 * Install a signed activation file received from the developer.
 *
 * Usage:
 *   php artisan app:install-activation /path/to/activation.dat
 */
class InstallActivation extends Command
{
    protected $signature   = 'app:install-activation {path : Full path to the activation.dat file}';
    protected $description = 'Install a signed activation file for this application.';

    public function __construct(
        private readonly ApplicationActivationService $activationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->argument('path');

        if($path == "storage") {
            $path = storage_path('activation.dat');
        }

        $this->info('');
        $this->line('  Invent — Activation Installer');
        $this->line('  ─────────────────────────────────────────────');
        $this->info('');

        try {
            $result = $this->activationService->installActivationFile($path);
        } catch (\RuntimeException $e) {
            $this->error('  Activation FAILED: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (!$result->isFullyValid()) {
            $this->error('  Activation installed but verification failed: ' . ($result->message ?? 'Unknown error'));
            return self::FAILURE;
        }

        $payload = $result->payload;
        $expiry  = \Carbon\Carbon::parse($payload['expires_at'])->format('d F Y');
        $actDate = \Carbon\Carbon::parse($payload['activated_at'])->format('d F Y');

        $this->info('  ✓ Activation verified successfully.');
        $this->info('');
        $this->line('  Product:       ' . ($payload['product'] ?? '-'));
        $this->line('  Installation:  ' . ($payload['installation_id'] ?? '-'));
        $this->line('  Activation ID: ' . ($payload['activation_id'] ?? '-'));
        $this->line('  Activated:     ' . $actDate);
        $this->line('  Expires:       ' . $expiry);
        $this->line('  Status:        <fg=green>ACTIVE</>');

        if ($result->daysRemaining !== null) {
            $this->line('  Days Left:     ' . $result->daysRemaining);
        }

        $this->info('');

        return self::SUCCESS;
    }
}
