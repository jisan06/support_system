<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    /**
     * Returns true if the application is considered installed.
     * Checks APP_INSTALLED env flag first, then falls back to the storage/installed file.
     */
    protected function isInstalled(): bool
    {
        // If APP_INSTALLED=true in .env, skip the installer entirely
        if (env('APP_INSTALLED', false) === true || env('APP_INSTALLED') === 'true') {
            return true;
        }

        return file_exists(storage_path('installed'));
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->isInstalled()) {
            if (!$request->is('install*')) {
                return redirect()->route('install.check');
            }
        }

        return $next($request);
    }
}
