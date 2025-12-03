<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Traits\HasStatus;
use Spatie\Permission\Traits\HasRoles;

/**
 * Admin User Model
 * 
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $status
 * @property string $password
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, HasRoles, HasStatus;

    /**
     * The primary key type
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password'
    ];

    /**
     * The attributes that should be hidden
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get available statuses
     */
    public function getAvailableStatuses(): array
    {
        return ['active', 'inactive', 'suspended', 'banned'];
    }

    /**
     * Update user status safely
     */
    public function updateStatus(string $status): bool
    {
        if (!in_array($status, $this->getAvailableStatuses())) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }
        
        $this->status = $status;
        return $this->save();
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();
    }
}
