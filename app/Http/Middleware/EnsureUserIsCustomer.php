<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure the authenticated user is a customer
 */
class EnsureUserIsCustomer
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Check if user is a Customer model instance
        if (!$user instanceof \Modules\Customer\Models\Customer) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Customer access required.',
            ], 403);
        }

        return $next($request);
    }
}
