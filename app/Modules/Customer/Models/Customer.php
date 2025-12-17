<?php

declare(strict_types=1);

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Traits\HasStatus;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Builder;

/**
 * Customer Model
 *
 * @property string $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property string $status
 * @property array $preferences
 */
class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, HasStatus, HasRoles;

    /**
     * The guard name
     */
    protected $guard = 'customer';

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
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'date_of_birth',
        'gender',
        'avatar',
        'avatar_url',
        'preferences',
        'oauth_provider',
        'oauth_provider_id',
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
            'date_of_birth' => 'date',
            'preferences' => 'array',
        ];
    }

    /**
     * Get available statuses
     */
    public function getAvailableStatuses(): array
    {
        return ['active', 'inactive', 'suspended'];
    }

    /**
     * Get customer's full name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Customer addresses
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get default shipping address
     */
    public function defaultShippingAddress()
    {
        return $this->hasOne(Address::class)
            ->where('type', 'shipping')
            ->where('is_default', true);
    }

    /**
     * Get default billing address
     */
    public function defaultBillingAddress()
    {
        return $this->hasOne(Address::class)
            ->where('type', 'billing')
            ->where('is_default', true);
    }

    /**
     * Update customer status safely
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
     * Check if customer has an OAuth provider linked
     */
    public function hasOAuthProvider(): bool
    {
        return !empty($this->oauth_provider) && !empty($this->oauth_provider_id);
    }

    /**
     * Link OAuth provider to this customer
     */
    public function linkOAuthProvider(string $provider, string $providerId, ?string $avatarUrl = null): void
    {
        $this->oauth_provider = $provider;
        $this->oauth_provider_id = $providerId;

        if ($avatarUrl) {
            $this->avatar_url = $avatarUrl;
        }

        // Mark email as verified for OAuth users
        if (!$this->email_verified_at) {
            $this->email_verified_at = now();
        }

        $this->save();
    }

    /**
     * Scope for searching customers
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();
    }
}
