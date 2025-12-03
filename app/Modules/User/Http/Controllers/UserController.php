<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\User\Models\User;
use Modules\User\Services\UserService;

/**
 * Admin User Management Controller
 */
class UserController extends ApiController
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * List users
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with('roles')
            ->when($request->search, fn($q) => $q->search($request->search))
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($users);
    }

    /**
     * Create user
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = $this->userService->createUser($validated);
        $user->assignRole($validated['role']);

        return $this->createdResponse($user, 'User created successfully');
    }

    /**
     * Show user
     */
    public function show(string $id): JsonResponse
    {
        $user = User::with('roles')->findOrFail($id);
        return $this->successResponse($user);
    }

    /**
     * Update user
     */
    public function update(string $id, Request $request): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'sometimes|string|exists:roles,name',
            'phone' => 'nullable|string|max:20',
            'status' => 'sometimes|in:active,inactive,suspended',
        ]);

        $user->update($validated);

        if (isset($validated['password'])) {
            $user->update(['password' => \Hash::make($validated['password'])]);
        }

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return $this->successResponse($user, 'User updated successfully');
    }

    /**
     * Delete user
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            return $this->errorResponse('Cannot delete yourself', 403);
        }

        $user->delete();

        return $this->successResponse(null, 'User deleted successfully');
    }
}
