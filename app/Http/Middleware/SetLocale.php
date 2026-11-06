<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['en', 'bn', 'ar', 'es'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', setting('default_language', 'en'));

        if (in_array($locale, self::SUPPORTED_LOCALES)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}