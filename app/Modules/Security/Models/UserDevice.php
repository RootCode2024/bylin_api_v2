<?php

declare(strict_types=1);

namespace Modules\Security\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\Models\BaseModel;

class UserDevice extends BaseModel
{
    use HasUuids;

    protected $table = 'user_devices';

    protected $fillable = [
        'user_id',
        'user_type',
        'device_fingerprint',
        'device_name',
        'device_type',
        'browser',
        'platform',
        'last_ip',
        'last_country',
        'last_city',
        'first_seen_at',
        'last_seen_at',
        'is_trusted',
        'is_blocked',
        'blocked_reason',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'is_trusted' => 'boolean',
        'is_blocked' => 'boolean',
    ];

    /**
     * Get the user that owns this device
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for trusted devices
     */
    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true)
                    ->where('is_blocked', false);
    }

    /**
     * Scope for blocked devices
     */
    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
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
     * Mark device as trusted
     */
    public function markAsTrusted(): void
    {
        $this->is_trusted = true;
        $this->save();
    }

    /**
     * Block this device
     */
    public function block(string $reason = null): void
    {
        $this->is_blocked = true;
        $this->blocked_reason = $reason;
        $this->save();
    }

    /**
     * Unblock this device
     */
    public function unblock(): void
    {
        $this->is_blocked = false;
        $this->blocked_reason = null;
        $this->save();
    }

    /**
     * Update device activity
     */
    public function updateActivity(string $ip, ?string $country = null, ?string $city = null): void
    {
        $this->last_seen_at = now();
        $this->last_ip = $ip;
        
        if ($country) {
            $this->last_country = $country;
        }
        
        if ($city) {
            $this->last_city = $city;
        }
        
        $this->save();
    }

    /**
     * Get formatted device info
     */
    public function getDeviceInfoAttribute(): string
    {
        return "{$this->device_name} ({$this->platform})";
    }

    /**
     * Get last location
     */
    public function getLastLocationAttribute(): string
    {
        $parts = array_filter([$this->last_city, $this->last_country]);
        return implode(', ', $parts) ?: 'Unknown';
    }
}
