<?php

namespace App\Http\Middleware;

use App\Enums\TwoFactorTypeEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffTwoFactor
{
    /**
     * Routes that staff without 2FA are allowed to access.
     *
     * @var array<string>
     */
    protected array $allowedRoutes = [
        'settings.security.totp',
        'settings.two-factor.totp.setup',
        'settings.two-factor.totp.store',
        'settings.security.confirm-password',
        'settings.security.backup-codes',
        'settings.two-factor.backup-codes.regenerate',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isStaff()) {
            return $next($request);
        }

        $hasTotp = $user->twoFactors()
            ->where('type', TwoFactorTypeEnum::TOTP)
            ->exists();

        if ($hasTotp) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), $this->allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()->route('settings.security.totp');
    }
}
