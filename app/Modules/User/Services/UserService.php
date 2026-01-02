<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
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
     * Update user profile (name, email, phone, bio)
     */
    public function updateProfile(string $userId, array $data): User
    {
        return $this->transaction(function () use ($userId, $data) {
            $user = $this->userRepository->findOrFail($userId);

            // Validate email uniqueness if changed
            if (isset($data['email']) && $data['email'] !== $user->email) {
                if ($this->userRepository->findByEmail($data['email'])) {
                    throw new BusinessException('Cette adresse email est déjà utilisée');
                }
            }

            // Only update allowed profile fields
            $allowedFields = ['name', 'email', 'phone', 'bio'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));

            $user->update($updateData);

            $this->logInfo('Profile updated', ['user_id' => $user->id]);

            return $user->fresh()->load('roles.permissions');
        });
    }

    /**
     * Update user avatar
     */
    public function updateAvatar(string $userId, UploadedFile $file): User
    {
        return $this->transaction(function () use ($userId, $file) {
            $user = $this->userRepository->findOrFail($userId);

            // Validate file
            if (!in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'gif'])) {
                throw new BusinessException('Format de fichier non supporté');
            }

            if ($file->getSize() > 1024 * 1024) { // 1MB
                throw new BusinessException('Le fichier est trop volumineux (1MB max)');
            }

            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Store new avatar
            $path = $file->store('avatars', 'public');

            $user->update([
                'avatar' => $path,
                'avatar_url' => config('app.url', 'http://localhost:8000') . Storage::url($path)
            ]);

            $this->logInfo('Avatar updated', ['user_id' => $user->id]);

            return $user->fresh()->load('roles.permissions');
        });
    }

    /**
     * Delete user avatar
     */
    public function deleteAvatar(string $userId): User
    {
        return $this->transaction(function () use ($userId) {
            $user = $this->userRepository->findOrFail($userId);

            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);

                $user->update([
                    'avatar' => null,
                    'avatar_url' => null
                ]);

                $this->logInfo('Avatar deleted', ['user_id' => $user->id]);
            }

            return $user->fresh()->load('roles.permissions');
        });
    }

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

            return $user->load('roles.permissions');
        });
    }

    /**
     * Update user (admin operation - can change status, roles, etc.)
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

            return $user->fresh()->load('roles.permissions');
        });
    }

    /**
     * Delete user
     */
    public function deleteUser(string $id): bool
    {
        return $this->transaction(function () use ($id) {
            $user = $this->userRepository->findOrFail($id);

            // Delete avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

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
            throw new BusinessException('Le mot de passe actuel est incorrect');
        }

        $updated = $user->update([
            'password' => Hash::make($newPassword)
        ]);

        $this->logInfo('Password changed', ['user_id' => $user->id]);

        return $updated;
    }
}
