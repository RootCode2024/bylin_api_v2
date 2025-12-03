<?php

declare(strict_types=1);

namespace Modules\Promotion\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;

class Promotion extends BaseModel
{
    use HasUuids, SoftDeletes;

    protected $table = 'promotions';

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'value',
        'min_purchase_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_limit_per_customer',
        'usage_count',
        'is_active',
        'starts_at',
        'expires_at',
        'applicable_products',
        'applicable_categories',
        'metadata',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_limit_per_customer' => 'integer',
        'usage_count' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
        'metadata' => 'array',
    ];

    // Type constants
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';
    public const TYPE_BUY_X_GET_Y = 'buy_x_get_y';

    /**
     * Get the promotion usages
     */
    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    /**
     * Scope for active promotions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', now());
            });
    }

    /**
     * Scope for code search
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper($code));
    }

    /**
     * Check if promotion is currently active
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if promotion has reached usage limit
     */
    public function hasReachedLimit(): bool
    {
        if (!$this->usage_limit) {
            return false;
        }

        return $this->usage_count >= $this->usage_limit;
    }

    /**
     * Check if customer has reached their usage limit
     */
    public function hasCustomerReachedLimit(string $customerId): bool
    {
        $customerUsageCount = $this->usages()
            ->where('customer_id', $customerId)
            ->count();

        return $customerUsageCount >= $this->usage_limit_per_customer;
    }

    /**
     * Check if promotion is applicable to given amount
     */
    public function isApplicableToAmount(float $amount): bool
    {
        if (!$this->min_purchase_amount) {
            return true;
        }

        return $amount >= $this->min_purchase_amount;
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            $discount = ($amount * $this->value) / 100;
        } elseif ($this->type === self::TYPE_FIXED) {
            $discount = $this->value;
        } else {
            // For buy_x_get_y, calculation is more complex and handled in service
            return 0;
        }

        // Apply max discount limit if set
        if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
            $discount = $this->max_discount_amount;
        }

        // Discount cannot exceed the amount
        if ($discount > $amount) {
            $discount = $amount;
        }

        return round($discount, 2);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($promotion) {
            // Normalize code to uppercase
            if ($promotion->code) {
                $promotion->code = strtoupper($promotion->code);
            }
        });

        static::updating(function ($promotion) {
            // Normalize code to uppercase
            if ($promotion->isDirty('code') && $promotion->code) {
                $promotion->code = strtoupper($promotion->code);
            }
        });
    }
}
