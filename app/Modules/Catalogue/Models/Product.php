<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Spatie\MediaLibrary\HasMedia;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\HasStatus;
use Modules\Catalogue\Models\Brand;
use Modules\Core\Traits\Searchable;
use Modules\Catalogue\Models\Collection;
use Modules\Catalogue\Enums\ProductStatus;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Product extends BaseModel implements HasMedia
{
    use HasStatus, Searchable, InteractsWithMedia, SoftDeletes;

    protected $searchableFields = ['name', 'sku', 'description'];

    protected $fillable = [
        'brand_id',
        'collection_id',
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
        'is_new',
        'is_on_sale',

        'track_inventory',
        'stock_quantity',
        'low_stock_threshold',
        'barcode',

        'weight',
        'dimensions',

        'is_preorder_enabled',
        'preorder_auto_enabled',
        'preorder_available_date',
        'preorder_limit',
        'preorder_count',
        'preorder_message',
        'preorder_terms',
        'preorder_enabled_at',

        'requires_authenticity',
        'authenticity_codes_count',

        'is_variable',
        'variation_attributes',

        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_data',

        'views_count',
        'rating_average',
        'rating_count',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'is_on_sale' => 'boolean',
            'track_inventory' => 'boolean',
            'is_variable' => 'boolean',

            'is_preorder_enabled' => 'boolean',
            'preorder_auto_enabled' => 'boolean',
            'preorder_available_date' => 'datetime',
            'preorder_enabled_at' => 'datetime',
            'preorder_limit' => 'integer',
            'preorder_count' => 'integer',

            'requires_authenticity' => 'boolean',
            'authenticity_codes_count' => 'integer',

            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'cost_price' => 'decimal:2',

            'weight' => 'decimal:2',
            'dimensions' => 'array',

            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',

            'rating_average' => 'decimal:2',
            'views_count' => 'integer',
            'rating_count' => 'integer',

            'meta_data' => 'array',
            'meta_keywords' => 'array',
            'variation_attributes' => 'array',

            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getAvailableStatuses(): array
    {
        return ['draft', 'active', 'inactive', 'out_of_stock', 'preorder', 'discontinued'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function authenticityCodes(): HasMany
    {
        return $this->hasMany(ProductAuthenticityCode::class);
    }

    public function isBylinProduct(): bool
    {
        return $this->brand && $this->brand->is_bylin_brand;
    }

    public function requiresAuthenticity(): bool
    {
        return $this->requires_authenticity === true;
    }

    public function getAvailableAuthenticityCodesCount(): int
    {
        return $this->authenticityCodes()->where('is_authentic', true)->where('is_activated', false)->count();
    }

    public function scopeBylin($query)
    {
        return $query->whereHas('brand', function ($q) {
            $q->where('is_bylin_brand', true);
        });
    }

    public function scopeRequiresAuthenticity($query)
    {
        return $query->where('requires_authenticity', true);
    }

    public function scopeInCollection($query, string $collectionId)
    {
        return $query->where('collection_id', $collectionId);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')->withTimestamps();
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes')->withPivot('attribute_value_id')->withTimestamps();
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity > 0 && $this->stock_quantity <= ($this->low_stock_threshold ?? 5);
    }

    public function canPreorder(): bool
    {
        if (!$this->is_preorder_enabled) return false;

        if ($this->preorder_limit !== null && $this->preorder_count >= $this->preorder_limit) return false;

        return true;
    }

    public function getDiscountPercentageAttribute(): ?float
    {
        if ($this->compare_price && $this->compare_price > $this->price) return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 2);

        return null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', ProductStatus::ACTIVE);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopePreorder($query)
    {
        return $query->where('is_preorder_enabled', true);
    }

    public function scopeInCategory($query, string $categoryId)
    {
        return $query->whereHas('categories', function ($q) use ($categoryId) {
            $q->where('categories.id', $categoryId);
        });
    }

    public function scopeByBrand($query, string $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    public function scopePriceBetween($query, float $min, float $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useFallbackUrl('/images/product-placeholder.jpg')->useFallbackPath(public_path('/images/product-placeholder.jpg'));
    }
}
