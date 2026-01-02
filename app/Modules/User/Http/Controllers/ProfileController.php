<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\User\Services\UserService;

/**
 * Profile Controller
 *
 * Manages authenticated user's profile
 */
class ProfileController extends ApiController
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Get current user profile
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load('roles.permissions')
        );
    }

    /**
     * Update profile (name, email, phone, bio)
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
        ]);

        $user = $this->userService->updateProfile(
            $request->user()->id,
            $validated
        );

        return response()->json($user);
    }

    /**
     * Upload avatar
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,gif|max:1024', // 1MB
        ]);

        $user = $this->userService->updateAvatar(
            $request->user()->id,
            $validated['avatar']
        );

        return response()->json($user);
    }

    /**
     * Delete avatar
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $this->userService->deleteAvatar($request->user()->id);
        return response()->json($user);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $this->userService->changePassword(
            $request->user()->id,
            $validated['current_password'],
            $validated['new_password']
        );

        return $this->successResponse(null, 'Mot de passe modifié avec succès');
    }

    /**
     * Delete account
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        // Vérifier si l'utilisateur est super admin
        if ($user->hasRole('super_admin')) {
            // Compter les autres super admins
            $superAdminsCount = \Modules\User\Models\User::role('super_admin')
                ->where('id', '!=', $user->id)
                ->where('status', 'active')
                ->count();

            if ($superAdminsCount === 0) {
                return $this->errorResponse(
                    'Vous devez nommer un autre super administrateur avant de supprimer votre compte',
                    403
                );
            }
        }

        $this->userService->deleteUser($user->id);

        return $this->successResponse(null, 'Compte supprimé avec succès');
    }
}
