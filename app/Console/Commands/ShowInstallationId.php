<?php

namespace App\Console\Commands;

use App\Services\Activation\ApplicationActivationService;
use Illuminate\Console\Command;

/**
 * Display or generate the installation ID for this deployment.
 * The developer needs this ID to generate a signed activation file.
 *
 * Usage:
 *   php artisan app:installation-id
 */
class ShowInstallationId extends Command
{
    protected $signature   = 'app:installation-id';
    protected $description = 'Show the installation ID for this deployment (share with developer to generate an activation).';

    public function __construct(
        private readonly ApplicationActivationService $activationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $id = $this->activationService->installationId();

        $this->info('');
        $this->line('  Installation ID');
        $this->line('  ───────────────');
        $this->info('  ' . $id);
        $this->info('');
        $this->line('  Share this ID with the software developer/vendor');
        $this->line('  to receive a signed activation file.');
        $this->info('');

        return self::SUCCESS;
    }
}
