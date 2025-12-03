<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Exceptions\BusinessException;
use Modules\Core\Services\BaseService;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

/**
 * Authentication Service for Admin Users
 * 
 * Handles login, logout, and token management
 */
class AuthService extends BaseService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Authenticate user and generate token
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !Hash::check($password, $user->password)) {
            throw new BusinessException('Invalid credentials', 401);
        }

        if ($user->status !== 'active') {
            throw new BusinessException('Account is not active', 403);
        }

        // Revoke all previous tokens
        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        $this->logInfo('User logged in', ['user_id' => $user->id]);

        return [
            'user' => $user->load('roles'),
            'token' => $token,
        ];
    }

    /**
     * Logout user
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();
        $this->logInfo('User logged out', ['user_id' => $user->id]);
    }

    /**
     * Refresh user token
     */
    public function refreshToken(User $user): string
    {
        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;
        
        $this->logInfo('Token refreshed', ['user_id' => $user->id]);
        
        return $token;
    }
}
