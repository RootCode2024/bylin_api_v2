<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure the user's email is verified
 */
class EnsureEmailIsVerified
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

        // Check if email is verified
        if (is_null($user->email_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Email address not verified. Please verify your email to continue.',
            ], 403);
        }

        return $next($request);
    }
}
