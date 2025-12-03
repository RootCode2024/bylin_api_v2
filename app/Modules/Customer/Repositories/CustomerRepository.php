<?php

declare(strict_types=1);

namespace Modules\Customer\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\Customer\Models\Customer;

/**
 * Customer Repository
 * 
 * Handles data access for customers
 */
class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    /**
     * Find customer by email
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get active customers
     */
    public function getActive()
    {
        return $this->model->active()->get();
    }

    /**
     * Search customers
     */
    public function search(string $keyword, int $perPage = 15)
    {
        return $this->model
            ->where(function ($query) use ($keyword) {
                $query->where('first_name', 'ILIKE', "%{$keyword}%")
                    ->orWhere('last_name', 'ILIKE', "%{$keyword}%")
                    ->orWhere('email', 'ILIKE', "%{$keyword}%")
                    ->orWhere('phone', 'ILIKE', "%{$keyword}%");
            })
            ->paginate($perPage);
    }

    /**
     * Get customers with addresses
     */
    public function withAddresses()
    {
        return $this->model->with('addresses')->get();
    }
}
