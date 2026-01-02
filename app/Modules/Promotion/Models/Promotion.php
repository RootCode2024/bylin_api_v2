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
        'value' => 'integer',
        'min_purchase_amount' => 'integer',
        'max_discount_amount' => 'integer',
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

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED_AMOUNT = 'fixed_amount';
    public const TYPE_BUY_X_GET_Y = 'buy_x_get_y';

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

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

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper(trim($code)));
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->whereNotNull('starts_at')
            ->where('starts_at', '>', now());
    }

    public function scopeExhausted($query)
    {
        return $query->whereNotNull('usage_limit')
            ->whereColumn('usage_count', '>=', 'usage_limit');
    }

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

    public function hasReachedLimit(): bool
    {
        if (!$this->usage_limit) {
            return false;
        }

        return $this->usage_count >= $this->usage_limit;
    }

    public function hasCustomerReachedLimit(string $customerId): bool
    {
        if (!$this->usage_limit_per_customer) {
            return false;
        }

        $customerUsageCount = $this->usages()
            ->where('customer_id', $customerId)
            ->count();

        return $customerUsageCount >= $this->usage_limit_per_customer;
    }

    /**
     * Check if promotion is applicable to given amount
     * @param int $amount Montant en centimes/francs
     */
    public function isApplicableToAmount(int $amount): bool
    {
        if (!$this->min_purchase_amount) {
            return true;
        }

        return $amount >= $this->min_purchase_amount;
    }

    /**
     * Calculate discount amount
     * @param int $amount Montant en centimes/francs
     * @return int Remise en centimes/francs
     */
    public function calculateDiscount(int $amount): int
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            // Calcul avec arrondi pour éviter les pertes de précision
            $discount = (int) round(($amount * $this->value) / 100);
        } elseif ($this->type === self::TYPE_FIXED_AMOUNT) {
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

        return $discount;
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($promotion) {
            if ($promotion->code) {
                $promotion->code = strtoupper($promotion->code);
            }

            if ($promotion->usage_count === null) {
                $promotion->usage_count = 0;
            }

            if ($promotion->usage_limit_per_customer === null) {
                $promotion->usage_limit_per_customer = 1;
            }
        });

        static::updating(function ($promotion) {
            if ($promotion->isDirty('code') && $promotion->code) {
                $promotion->code = strtoupper($promotion->code);
            }
        });
    }
}
