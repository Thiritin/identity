<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromBrowser
{
    /** @var array<string> */
    protected array $supportedLocales = ['en', 'de'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->getPreferredLanguage($this->supportedLocales) ?? config('app.locale');

        app()->setLocale($locale);

        return $next($request);
    }
}
