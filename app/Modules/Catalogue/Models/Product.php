<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Spatie\MediaLibrary\HasMedia;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\HasStatus;
use Modules\Catalogue\Models\Brand;
use Modules\Core\Traits\Searchable;
use Modules\Catalogue\Enums\ProductStatus;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Model
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $sku
 * @property float $price
 * @property int $stock_quantity
 * @property bool $is_preorder_enabled
 * @property bool $preorder_auto_enabled
 */
class Product extends BaseModel implements HasMedia
{
    use HasStatus, Searchable, InteractsWithMedia, SoftDeletes;

    protected $searchableFields = ['name', 'sku', 'description'];

    protected $fillable = [
        // Basic info
        'brand_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',

        // Pricing
        'price',
        'compare_price',
        'cost_price',

        // Status
        'status',
        'is_featured',
        'is_new',
        'is_on_sale',

        // Inventory
        'track_inventory',
        'stock_quantity',
        'low_stock_threshold',
        'barcode',

        // Physical
        'weight',
        'dimensions',

        // Preorder - NOMS UNIFORMISÉS
        'is_preorder_enabled',      // ✅ Précommande activée ?
        'preorder_auto_enabled',    // ✅ Activation automatique ?
        'preorder_available_date',  // ✅ Date de disponibilité
        'preorder_limit',           // ✅ Limite de précommandes
        'preorder_count',           // ✅ Nombre de précommandes
        'preorder_message',         // ✅ Message personnalisé
        'preorder_terms',           // ✅ Conditions
        'preorder_enabled_at',      // ✅ Date d'activation

        // Bylin Authenticity
        'requires_authenticity',
        'authenticity_codes_count',

        // Variations
        'is_variable',
        'variation_attributes',

        // SEO
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_data',

        // Stats
        'views_count',
        'rating_average',
        'rating_count',
    ];

    protected function casts(): array
    {
        return [
            // Status & Booleans
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'is_on_sale' => 'boolean',
            'track_inventory' => 'boolean',
            'is_variable' => 'boolean',

            // Preorder
            'is_preorder_enabled' => 'boolean',
            'preorder_auto_enabled' => 'boolean',
            'preorder_available_date' => 'datetime',
            'preorder_enabled_at' => 'datetime',
            'preorder_limit' => 'integer',
            'preorder_count' => 'integer',

            // Authenticity
            'requires_authenticity' => 'boolean',
            'authenticity_codes_count' => 'integer',

            // Pricing
            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'cost_price' => 'decimal:2',

            // Physical
            'weight' => 'decimal:2',
            'dimensions' => 'array',

            // Stock
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',

            // Stats
            'rating_average' => 'decimal:2',
            'views_count' => 'integer',
            'rating_count' => 'integer',

            // Metadata
            'meta_data' => 'array',
            'meta_keywords' => 'array',
            'variation_attributes' => 'array',

            // Timestamps
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getAvailableStatuses(): array
    {
        return ['draft', 'active', 'inactive', 'out_of_stock', 'preorder', 'discontinued'];
    }

    /**
     * Brand relationship
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Categories relationship
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->withTimestamps();
    }

    /**
     * Variations relationship
     */
    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    /**
     * Attributes relationship
     */
    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes')
            ->withPivot('attribute_value_id')
            ->withTimestamps();
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Check if product is low on stock
     */
    public function isLowStock(): bool
    {
        return $this->stock_quantity > 0
            && $this->stock_quantity <= ($this->low_stock_threshold ?? 5);
    }

    /**
     * Check if product can be preordered
     */
    public function canPreorder(): bool
    {
        if (!$this->is_preorder_enabled) {
            return false;
        }

        // Check limit
        if ($this->preorder_limit !== null && $this->preorder_count >= $this->preorder_limit) {
            return false;
        }

        return true;
    }

    /**
     * Get discount percentage if on sale
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if ($this->compare_price && $this->compare_price > $this->price) {
            return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 2);
        }
        return null;
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', ProductStatus::ACTIVE);
    }

    /**
     * Scope for featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for in-stock products
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Scope for preorder products
     */
    public function scopePreorder($query)
    {
        return $query->where('is_preorder_enabled', true);
    }

    /**
     * Scope for products by category
     */
    public function scopeInCategory($query, string $categoryId)
    {
        return $query->whereHas('categories', function ($q) use ($categoryId) {
            $q->where('categories.id', $categoryId);
        });
    }

    /**
     * Scope for products by brand
     */
    public function scopeByBrand($query, string $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    /**
     * Scope for price range filter
     */
    public function scopePriceBetween($query, float $min, float $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useFallbackUrl('/images/product-placeholder.jpg')
            ->useFallbackPath(public_path('/images/product-placeholder.jpg'));
    }
}
