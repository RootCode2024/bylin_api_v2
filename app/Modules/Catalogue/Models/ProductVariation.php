<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Modules\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            'price' => 'integer',
            'compare_price' => 'integer',
            'cost_price' => 'integer',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
            'attributes' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ========================================================================
    // MÉTHODES MÉTIER - Utilisent le threshold du parent
    // ========================================================================

    /**
     * Vérifie si la variation est en stock
     */
    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Vérifie si la variation est en stock faible
     * Utilise le threshold du produit parent
     */
    public function isLowStock(): bool
    {
        // Charger le produit parent si nécessaire
        if (!$this->relationLoaded('product')) {
            $this->load('product');
        }

        $threshold = $this->product->low_stock_threshold ?? 10;
        return $this->stock_quantity > 0 && $this->stock_quantity <= $threshold;
    }

    /**
     * Obtient le threshold depuis le parent
     */
    public function getLowStockThreshold(): int
    {
        if (!$this->relationLoaded('product')) {
            $this->load('product');
        }

        return $this->product->low_stock_threshold ?? 10;
    }

    /**
     * Met à jour le statut de stock de la variation
     */
    public function updateStockStatus(): void
    {
        $oldStatus = $this->stock_status;

        if ($this->stock_quantity <= 0) {
            $this->stock_status = 'out_of_stock';
        } elseif ($this->isLowStock()) {
            $this->stock_status = 'low_stock';
        } else {
            $this->stock_status = 'in_stock';
        }

        if ($oldStatus !== $this->stock_status) {
            $this->saveQuietly();
        }
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Scope pour les variations en stock faible
     * Utilise un JOIN pour accéder au threshold du parent
     */
    public function scopeLowStock($query, ?int $threshold = null)
    {
        $threshold = $threshold ?? 10;

        return $query->join('products', 'product_variations.product_id', '=', 'products.id')
            ->where('product_variations.stock_quantity', '>', 0)
            ->whereRaw('product_variations.stock_quantity <= COALESCE(products.low_stock_threshold, ?)', [$threshold])
            ->select('product_variations.*'); // Important : ne sélectionner que les colonnes de product_variations
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', '<=', 0);
    }
}
