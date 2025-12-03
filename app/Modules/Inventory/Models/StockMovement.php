<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Models\ProductVariation;
use Modules\Core\Models\BaseModel;
use Modules\User\Models\User;

class StockMovement extends BaseModel
{
    use HasUuids;

    protected $table = 'stock_movements';

    protected $fillable = [
        'product_id',
        'variation_id',
        'type',
        'reason',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_id',
        'reference_type',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
    ];

    // Type constants
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';

    // Reason constants
    public const REASON_PURCHASE = 'purchase';
    public const REASON_SALE = 'sale';
    public const REASON_RETURN = 'return';
    public const REASON_ADJUSTMENT = 'adjustment';
    public const REASON_DAMAGED = 'damaged';
    public const REASON_LOST = 'lost';
    public const REASON_FOUND = 'found';
    public const REASON_INITIAL_STOCK = 'initial_stock';

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variation (if applicable)
     */
    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    /**
     * Get the user who created this movement
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope by type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope by reason
     */
    public function scopeReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * Scope for product
     */
    public function scopeForProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope for variation
     */
    public function scopeForVariation($query, string $variationId)
    {
        return $query->where('variation_id', $variationId);
    }

    /**
     * Scope for stock in movements
     */
    public function scopeStockIn($query)
    {
        return $query->where('type', self::TYPE_IN);
    }

    /**
     * Scope for stock out movements
     */
    public function scopeStockOut($query)
    {
        return $query->where('type', self::TYPE_OUT);
    }

    /**
     * Scope ordered by latest
     */
    public function scopeLatest($query, string $column = 'created_at')
    {
        return $query->orderBy($column, 'desc');
    }

    /**
     * Create a stock movement and update product/variation stock
     */
    public static function createMovement(array $data): self
    {
        // Determine if it's for a product or variation
        $isVariation = !empty($data['variation_id']);
        
        if ($isVariation) {
            $variation = ProductVariation::findOrFail($data['variation_id']);
            $currentStock = $variation->stock_quantity;
        } else {
            $product = Product::findOrFail($data['product_id']);
            $currentStock = $product->stock_quantity;
        }

        // Calculate new stock based on type
        $quantity = $data['quantity'];
        $type = $data['type'];

        if ($type === self::TYPE_IN) {
            $newStock = $currentStock + $quantity;
        } elseif ($type === self::TYPE_OUT) {
            $newStock = $currentStock - abs($quantity);
            $quantity = -abs($quantity); // Ensure negative for OUT
        } else {
            // Adjustment can be positive or negative
            $newStock = $currentStock + $quantity;
        }

        // Prevent negative stock
        if ($newStock < 0) {
            throw new \Exception('Insufficient stock. Cannot reduce stock below zero.');
        }

        // Create the movement record
        $movement = self::create([
            'product_id' => $data['product_id'],
            'variation_id' => $data['variation_id'] ?? null,
            'type' => $type,
            'reason' => $data['reason'],
            'quantity' => $quantity,
            'quantity_before' => $currentStock,
            'quantity_after' => $newStock,
            'reference_id' => $data['reference_id'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);

        // Update the actual stock
        if ($isVariation) {
            $variation->update(['stock_quantity' => $newStock]);
        } else {
            $product->update(['stock_quantity' => $newStock]);
        }

        return $movement;
    }

    /**
     * Record a sale
     */
    public static function recordSale(
        string $productId,
        int $quantity,
        ?string $variationId = null,
        ?string $orderId = null
    ): self {
        return self::createMovement([
            'product_id' => $productId,
            'variation_id' => $variationId,
            'type' => self::TYPE_OUT,
            'reason' => self::REASON_SALE,
            'quantity' => $quantity,
            'reference_id' => $orderId,
            'reference_type' => 'Order',
        ]);
    }

    /**
     * Record a return
     */
    public static function recordReturn(
        string $productId,
        int $quantity,
        ?string $variationId = null,
        ?string $orderId = null
    ): self {
        return self::createMovement([
            'product_id' => $productId,
            'variation_id' => $variationId,
            'type' => self::TYPE_IN,
            'reason' => self::REASON_RETURN,
            'quantity' => $quantity,
            'reference_id' => $orderId,
            'reference_type' => 'Order',
        ]);
    }

    /**
     * Get absolute quantity value
     */
    public function getAbsoluteQuantityAttribute(): int
    {
        return abs($this->quantity);
    }
}
