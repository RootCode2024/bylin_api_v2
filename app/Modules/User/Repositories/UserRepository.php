<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\User\Models\User;

/**
 * User Repository
 * 
 * Handles data access for admin users
 */
class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Find users by role
     */
    public function findByRole(string $role)
    {
        return $this->model->role($role)->get();
    }

    /**
     * Get active users
     */
    public function getActive()
    {
        return $this->model->active()->get();
    }

    /**
     * Search users
     */
    public function search(string $keyword, int $perPage = 15)
    {
        return $this->model
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'ILIKE', "%{$keyword}%")
                    ->orWhere('email', 'ILIKE', "%{$keyword}%");
            })
            ->paginate($perPage);
    }
}
