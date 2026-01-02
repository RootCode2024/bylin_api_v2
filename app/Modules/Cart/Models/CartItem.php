<?php

declare(strict_types=1);

namespace Modules\Cart\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Models\ProductVariation;
use Modules\Core\Models\BaseModel;

class CartItem extends BaseModel
{
    use HasUuids;

    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id',
        'product_id',
        'variation_id',
        'quantity',
        'price',
        'subtotal',
        'options',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'integer',
        'subtotal' => 'integer',
        'options' => 'array',
    ];

    /**
     * Get the cart that owns this item
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product for this item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variation for this item (if applicable)
     */
    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    /**
     * Calculate subtotal based on quantity and price
     */
    public function calculateSubtotal(): float
    {
        return $this->quantity * $this->price;
    }

    /**
     * Update quantity
     */
    public function updateQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        $this->subtotal = $this->calculateSubtotal();
        $this->save();

        return $this;
    }

    /**
     * Increment quantity
     */
    public function incrementQuantity(int $amount = 1): self
    {
        return $this->updateQuantity($this->quantity + $amount);
    }

    /**
     * Decrement quantity
     */
    public function decrementQuantity(int $amount = 1): self
    {
        $newQuantity = max(1, $this->quantity - $amount);
        return $this->updateQuantity($newQuantity);
    }

    /**
     * Get the current price from product
     */
    public function refreshPrice(): self
    {
        if ($this->variation_id) {
            $currentPrice = $this->variation->price ?? $this->product->price;
        } else {
            $currentPrice = $this->product->price;
        }

        $this->price = $currentPrice;
        $this->subtotal = $this->calculateSubtotal();
        $this->save();

        return $this;
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($cartItem) {
            // Calculate subtotal if not set
            if (!$cartItem->subtotal) {
                $cartItem->subtotal = $cartItem->calculateSubtotal();
            }
        });

        static::updating(function ($cartItem) {
            // Recalculate subtotal when quantity or price changes
            if ($cartItem->isDirty(['quantity', 'price'])) {
                $cartItem->subtotal = $cartItem->calculateSubtotal();
            }
        });
    }
}
