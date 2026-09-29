<?php

namespace App\Http\Controllers;

use App\Services\Activation\ApplicationActivationService;
use Illuminate\Http\Request;

/**
 * ActivationController
 *
 * Serves the informational pages shown when the application is locked/expired.
 * Does NOT expose any technical or cryptographic details to users.
 */
class ActivationController extends Controller
{
    public function __construct(
        private readonly ApplicationActivationService $activation
    ) {}

    /**
     * Application subscription expired page.
     */
    public function expired()
    {
        $expiresAt = $this->activation->expiresAt();

        return view('activation.expired', [
            'expiresAt' => $expiresAt,
        ]);
    }

    /**
     * Activation required page (no activation file found).
     */
    public function required()
    {
        return view('activation.required');
    }

    /**
     * Locked page (invalid signature / tampered data).
     * Shows a generic error — never exposes technical details.
     */
    public function locked()
    {
        return view('activation.locked');
    }
}
