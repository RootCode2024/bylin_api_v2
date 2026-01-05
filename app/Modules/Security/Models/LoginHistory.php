<?php

declare(strict_types=1);

namespace Modules\Security\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\Models\BaseModel;

class LoginHistory extends Model
{
    use HasUuids;

    protected $table = 'login_history';

    protected $fillable = [
        'user_id',
        'user_type',
        'ip_address',
        'user_agent',
        'device_type',
        'device_name',
        'browser',
        'platform',
        'country',
        'country_code',
        'city',
        'latitude',
        'longitude',
        'is_new_device',
        'is_new_location',
        'is_suspicious',
        'login_at',
        'logout_at',
    ];

    protected $casts = [
        'is_new_device' => 'boolean',
        'is_new_location' => 'boolean',
        'is_suspicious' => 'boolean',
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * Get the user that owns this login
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for recent logins
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('login_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for suspicious logins
     */
    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, string $userId, string $userType)
    {
        return $query->where('user_id', $userId)
                    ->where('user_type', $userType);
    }

    /**
     * Mark this login as logged out
     */
    public function markAsLoggedOut(): void
    {
        $this->logout_at = now();
        $this->save();
    }

    /**
     * Check if still active (not logged out)
     */
    public function isActive(): bool
    {
        return $this->logout_at === null;
    }

    /**
     * Get formatted location
     */
    public function getLocationAttribute(): string
    {
        $parts = array_filter([$this->city, $this->country]);
        return implode(', ', $parts) ?: 'Unknown';
    }
}
