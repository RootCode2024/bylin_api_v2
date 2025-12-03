<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Core\Exceptions\BusinessException;
use Modules\Core\Services\BaseService;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

/**
 * User Service
 * 
 * Handles business logic for admin user management
 */
class UserService extends BaseService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Create a new user
     */
    public function createUser(array $data): User
    {
        return $this->transaction(function () use ($data) {
            $this->validateRequired($data, ['name', 'email', 'password']);

            if ($this->userRepository->findByEmail($data['email'])) {
                throw new BusinessException('Email already exists');
            }

            $data['password'] = Hash::make($data['password']);
            $data['status'] = $data['status'] ?? 'active';

            $user = $this->userRepository->create($data);

            // Assign role if provided
            if (isset($data['role'])) {
                $user->assignRole($data['role']);
            }

            $this->logInfo('User created', ['user_id' => $user->id]);

            return $user;
        });
    }

    /**
     * Update user
     */
    public function updateUser(string $id, array $data): User
    {
        return $this->transaction(function () use ($id, $data) {
            $user = $this->userRepository->findOrFail($id);

            // Check email uniqueness if being changed
            if (isset($data['email']) && $data['email'] !== $user->email) {
                if ($this->userRepository->findByEmail($data['email'])) {
                    throw new BusinessException('Email already exists');
                }
            }

            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user->update($data);

            // Update role if provided
            if (isset($data['role'])) {
                $user->syncRoles([$data['role']]);
            }

            $this->logInfo('User updated', ['user_id' => $user->id]);

            return $user->fresh();
        });
    }

    /**
     * Delete user
     */
    public function deleteUser(string $id): bool
    {
        return $this->transaction(function () use ($id) {
            $deleted = $this->userRepository->delete($id);
            $this->logInfo('User deleted', ['user_id' => $id]);
            return $deleted;
        });
    }

    /**
     * Change user password
     */
    public function changePassword(string $id, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepository->findOrFail($id);

        if (!Hash::check($currentPassword, $user->password)) {
            throw new BusinessException('Current password is incorrect');
        }

        return $user->update([
            'password' => Hash::make($newPassword)
        ]);
    }
}
