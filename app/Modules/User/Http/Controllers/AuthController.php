<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Controllers\ApiController;
use Modules\User\Models\User;
use Modules\User\Services\UserService;
use Modules\Security\Services\LoginHistoryService;

/**
 * Authentication Controller for Admin Users (SPA - HTTP-only cookies)
 */
class AuthController extends ApiController
{
    public function __construct(
        private UserService $userService,
        private LoginHistoryService $loginHistoryService
    ) {}

    /**
     * Admin login (cookie-based authentication)
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            $this->loginHistoryService->recordFailedLogin($request->ip(), $validated['email']);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check user status
        if ($user->status !== 'active') {
            return $this->errorResponse('Account is not active', 403);
        }

        // Login using web guard (creates session)
        Auth::guard('web')->login($user);

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        // ✅ Pour nuxt-auth-sanctum : retourner directement l'objet user
        // (sans wrapper successResponse)
        return response()->json(
            $user->load('roles.permissions')
        );
    }

    /**
     * Admin registration
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = $this->userService->createUser($validated);

        // Auto-login after registration
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        // ✅ Pour nuxt-auth-sanctum : retourner directement l'objet user
        return response()->json(
            $user->load('roles.permissions')
        );
    }

    /**
     * Logout (destroy session)
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * Get authenticated user (for nuxt-auth-sanctum)
     * ✅ CORRECTION : Retourner directement l'objet sans wrapper
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load('roles.permissions')
        );
    }

    /**
     * Refresh user data (alternative endpoint avec successResponse)
     * Ce endpoint peut être utilisé par d'autres parties de votre app
     */
    public function refresh(Request $request): JsonResponse
    {
        return $this->successResponse(
            $request->user()->fresh()->load('roles.permissions'),
            'User data refreshed'
        );
    }
}
