<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerAccount
{
    /**
     * Handle an incoming request.
     * Enforces customer-only access: only authenticated users with account_type = customer can proceed.
     * Admin/internal users are strictly denied.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->guest(route('login'));
        }

        if (!$user->isCustomer()) {
            abort(403, 'Access denied. The customer portal is reserved for customer accounts.');
        }

        if ($user->status !== 'Active') {
            abort(403, 'Your account is inactive. Please contact support.');
        }

        return $next($request);
    }
}

