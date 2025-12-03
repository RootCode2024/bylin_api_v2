<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\HasStatus;
use Modules\Core\Traits\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Product Model
 * 
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $sku
 * @property float $price
 * @property string $status
 * @property int $stock_quantity
 */
class Product extends BaseModel implements HasMedia
{
    use HasStatus, Searchable, InteractsWithMedia;

    protected $searchableFields = ['name', 'sku', 'description'];

    protected $fillable = [
        'brand_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'price',
        'compare_price',
        'cost_price',
        'status',
        'is_featured',
        'track_inventory',
        'stock_quantity',
        'low_stock_threshold',
        'barcode',
        'weight',
        'dimensions',
        'meta_data',
        'views_count',
        'rating_average',
        'rating_count',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'weight' => 'decimal:2',
            'rating_average' => 'decimal:2',
            'dimensions' => 'array',
            'meta_data' => 'array',
            'is_featured' => 'boolean',
            'track_inventory' => 'boolean',
            'stock_quantity' => 'integer',
            'views_count' => 'integer',
            'rating_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getAvailableStatuses(): array
    {
        return ['draft', 'active', 'inactive', 'out_of_stock', 'discontinued'];
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
        return $this->stock_quantity > 0 && $this->stock_quantity <= $this->low_stock_threshold;
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
        return $query->where('status', 'active');
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
