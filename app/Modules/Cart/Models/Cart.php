<?php

declare(strict_types=1);

namespace Modules\Cart\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Customer\Models\Customer;

class Cart extends BaseModel
{
    use HasUuids;

    protected $table = 'carts';

    protected $fillable = [
        'customer_id',
        'session_id',
        'coupon_code',
        'discount_amount',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'total',
        'metadata',
        'expires_at',
    ];

    protected $casts = [
        'discount_amount' => 'integer',
        'subtotal' => 'integer',
        'tax_amount' => 'integer',
        'shipping_amount' => 'integer',
        'total' => 'integer',
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the customer that owns the cart
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the cart items
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Scope for active carts (not expired)
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope for guest carts
     */
    public function scopeGuest($query)
    {
        return $query->whereNull('customer_id')
                     ->whereNotNull('session_id');
    }

    /**
     * Scope for customer carts
     */
    public function scopeForCustomer($query, string $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope for session carts
     */
    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Check if cart is empty
     */
    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }

    /**
     * Check if cart is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Get total items count
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Calculate subtotal from items
     */
    public function calculateSubtotal(): float
    {
        return (float) $this->items->sum('subtotal');
    }

    /**
     * Calculate total
     */
    public function calculateTotal(): float
    {
        return $this->subtotal + $this->tax_amount + $this->shipping_amount - $this->discount_amount;
    }

    /**
     * Set expiration date (default: 30 days for guest, null for customer)
     */
    public function setExpiration(?int $days = null): void
    {
        if ($this->customer_id) {
            // Customer carts don't expire
            $this->expires_at = null;
        } else {
            // Guest carts expire after specified days (default: 30)
            $expirationDays = $days ?? config('cart.guest_cart_expiration_days', 30);
            $this->expires_at = now()->addDays($expirationDays);
        }

        $this->save();
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($cart) {
            // Set expiration for guest carts
            if (!$cart->customer_id && !$cart->expires_at) {
                $cart->expires_at = now()->addDays(
                    config('cart.guest_cart_expiration_days', 30)
                );
            }
        });
    }
}
