<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromBrowser
{
    /** @var array<string> */
    protected array $supportedLocales = ['en', 'de', 'fr'];

    public function handle(Request $request, Closure $next): Response
    {
        $userLocale = $request->user()?->preferences['locale'] ?? null;

        $locale = ($userLocale && in_array($userLocale, $this->supportedLocales))
            ? $userLocale
            : ($request->getPreferredLanguage($this->supportedLocales) ?? config('app.locale'));

        app()->setLocale($locale);

        return $next($request);
    }
}
