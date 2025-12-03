<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Security\Services\LoginHistoryService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Track login activity for authenticated users
 */
class TrackLoginActivity
{
    public function __construct(
        private LoginHistoryService $loginHistoryService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track successful logins
        if (Auth::check() && $this->isLoginRoute($request)) {
            try {
                $this->loginHistoryService->recordLogin(
                    Auth::user(),
                    $request->ip(),
                    $request->userAgent() ?? 'Unknown'
                );
            } catch (\Exception $e) {
                // Don't fail the request if tracking fails
                \Log::error('Failed to track login activity', [
                    'user_id' => Auth::id(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $response;
    }

    /**
     * Check if this is a login route
     */
    private function isLoginRoute(Request $request): bool
    {
        $route = $request->route()?->getName();
        
        return $route && (
            str_contains($route, 'login') ||
            str_contains($route, 'register')
        );
    }
}
