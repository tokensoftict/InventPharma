<?php

namespace App\Http\Middleware;

use App\Services\Activation\ApplicationActivationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureApplicationIsActive
 *
 * This middleware calls ApplicationActivationService — NOT the database.
 * It enforces the signed activation on every authenticated request.
 *
 * States handled:
 *  - 'active'   → allow request
 *  - 'expired'  → redirect to activation.expired
 *  - 'missing'  → redirect to activation.required
 *  - 'invalid'  → lock (signature bad / product mismatch)
 *  - 'tampered' → lock (database was manually edited)
 */
class EnsureApplicationIsActive
{
    public function __construct(
        private readonly ApplicationActivationService $activation
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Allow activation-related routes through without checking
        if ($this->isExcludedRoute($request)) {
            return $next($request);
        }

        $result = $this->activation->getActivation();

        return match ($result->status) {
            'active'   => $next($request),
            'expired'  => redirect()->route('activation.expired'),
            'missing'  => redirect()->route('activation.required'),
            'invalid',
            'tampered' => redirect()->route('activation.locked'),
            default    => redirect()->route('activation.locked'),
        };
    }

    private function isExcludedRoute(Request $request): bool
    {
        $excludedRoutes = [
            'activation.expired',
            'activation.required',
            'activation.locked',
            'logout',
            'login',
            'login_process',
        ];

        return in_array($request->route()?->getName(), $excludedRoutes, true);
    }
}
