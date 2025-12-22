<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Modules\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Variation Model
 *
 * @property string $id
 * @property string $product_id
 * @property string $sku
 * @property string $variation_name
 * @property float $price
 * @property float|null $compare_price
 * @property float|null $cost_price
 * @property int $stock_quantity
 * @property string $stock_status
 * @property string|null $barcode
 * @property bool $is_active
 * @property array $attributes
 */
class ProductVariation extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'variation_name',
        'price',
        'compare_price',
        'cost_price',
        'stock_quantity',
        'stock_status',
        'barcode',
        'is_active',
        'attributes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
            'attributes' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Product relationship
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if variation is in stock
     */
    public function isInStock(): bool
    {
        return $this->is_active
            && $this->stock_quantity > 0
            && $this->stock_status === 'in_stock';
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if ($this->compare_price && $this->compare_price > $this->price) {
            return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 2);
        }
        return null;
    }

    /**
     * Scope for active variations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for in-stock variations
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0)
            ->where('stock_status', 'in_stock');
    }

    /**
     * Update stock status based on quantity
     */
    public function updateStockStatus(): void
    {
        if ($this->stock_quantity <= 0) {
            $this->stock_status = 'out_of_stock';
        } elseif ($this->stock_quantity > 0 && $this->stock_status === 'out_of_stock') {
            $this->stock_status = 'in_stock';
        }

        $this->saveQuietly();
    }
}
