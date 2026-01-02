<?php

declare(strict_types=1);

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Models\ProductVariation;
use Modules\Core\Models\BaseModel;

class OrderItem extends BaseModel
{
    use HasUuids;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'variation_id',
        'product_name',
        'product_sku',
        'variation_name',
        'quantity',
        'price',
        'subtotal',
        'discount_amount',
        'total',
        'options',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'integer',
        'subtotal' => 'integer',
        'discount_amount' => 'integer',
        'total' => 'integer',
        'options' => 'array',
    ];

    /**
     * Get the order that owns this item
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
     * Calculate total based on quantity and price
     */
    public function calculateTotal(): float
    {
        $subtotal = $this->quantity * $this->price;
        return $subtotal - $this->discount_amount;
    }

    /**
     * Get display name (product with variation if exists)
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->variation_name) {
            return "{$this->product_name} - {$this->variation_name}";
        }

        return $this->product_name;
    }
}
