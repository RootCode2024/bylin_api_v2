<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Spatie\MediaLibrary\HasMedia;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\HasStatus;
use Modules\Core\Traits\Searchable;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;


class Collection extends BaseModel implements HasMedia
{
    use HasStatus, Searchable, InteractsWithMedia, SoftDeletes;

    protected $searchableFields = ['name', 'description', 'season', 'theme'];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'season',
        'theme',
        'release_date',
        'end_date',
        'is_active',
        'is_featured',
        'sort_order',
        'products_count',
        'total_stock',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_data',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'products_count' => 'integer',
            'total_stock' => 'integer',
            'release_date' => 'date',
            'end_date' => 'date',
            'meta_keywords' => 'array',
            'meta_data' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // ✅ Appends pour compatibilité avec le frontend
    protected $appends = ['cover_image_url', 'banner_image_url'];

    /**
     * Get available statuses for this model
     */
    public function getAvailableStatuses(): array
    {
        return ['active', 'inactive', 'upcoming', 'archived'];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Products belonging to this collection
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Active products only
     */
    public function activeProducts()
    {
        return $this->hasMany(Product::class)->active();
    }

    /**
     * Authenticity codes for products in this collection
     */
    public function authenticityCodes()
    {
        return $this->hasMany(ProductAuthenticityCode::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS - Using Spatie Media Library
    |--------------------------------------------------------------------------
    */

    /**
     * Get full URL for cover image from Media Library
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('cover');
        return $media ? $media->getUrl() : null;
    }

    /**
     * Get full URL for banner image from Media Library
     */
    public function getBannerImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('banner');
        return $media ? $media->getUrl() : null;
    }

    /**
     * Get cover image thumbnail (if conversions are set up)
     */
    public function getCoverImageThumbnailAttribute(): ?string
    {
        $media = $this->getFirstMedia('cover');
        return $media ? $media->getUrl('thumb') : null;
    }

    /**
     * Get banner image thumbnail (if conversions are set up)
     */
    public function getBannerImageThumbnailAttribute(): ?string
    {
        $media = $this->getFirstMedia('banner');
        return $media ? $media->getUrl('thumb') : null;
    }

    /**
     * Check if collection has a cover image
     */
    public function hasCoverImage(): bool
    {
        return $this->hasMedia('cover');
    }

    /**
     * Check if collection has a banner image
     */
    public function hasBannerImage(): bool
    {
        return $this->hasMedia('banner');
    }

    /**
     * Check if collection is upcoming (not yet released)
     */
    public function getIsUpcomingAttribute(): bool
    {
        return $this->release_date && $this->release_date->isFuture();
    }

    /**
     * Check if collection has ended
     */
    public function getIsEndedAttribute(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    /**
     * Check if collection is currently active
     */
    public function getIsCurrentAttribute(): bool
    {
        $now = now();

        $afterRelease = !$this->release_date || $this->release_date->isPast();
        $beforeEnd = !$this->end_date || $this->end_date->isFuture();

        return $this->is_active && $afterRelease && $beforeEnd;
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for active collections
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured collections
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for upcoming collections
     */
    public function scopeUpcoming($query)
    {
        return $query->where('release_date', '>', now());
    }

    /**
     * Scope for current collections (active and within date range)
     */
    public function scopeCurrent($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('release_date')
                    ->orWhere('release_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            });
    }

    /**
     * Scope for archived/ended collections
     */
    public function scopeArchived($query)
    {
        return $query->where('end_date', '<', now());
    }

    /**
     * Scope by season
     */
    public function scopeBySeason($query, string $season)
    {
        return $query->where('season', $season);
    }

    /**
     * Scope with products count
     */
    public function scopeWithProductsCount($query)
    {
        return $query->withCount('products');
    }

    /**
     * Scope with active products count
     */
    public function scopeWithActiveProductsCount($query)
    {
        return $query->withCount(['products as active_products_count' => function ($q) {
            $q->active();
        }]);
    }

    /**
     * Scope ordered by release date
     */
    public function scopeOrderedByRelease($query, string $direction = 'desc')
    {
        return $query->orderBy('release_date', $direction);
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Update products count cache
     */
    public function updateProductsCount(): void
    {
        $this->update([
            'products_count' => $this->products()->count(),
        ]);
    }

    /**
     * Update total stock cache
     */
    public function updateTotalStock(): void
    {
        $this->update([
            'total_stock' => $this->products()->sum('stock_quantity'),
        ]);
    }

    /**
     * Get collection status
     */
    public function getCollectionStatus(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->is_upcoming) {
            return 'upcoming';
        }

        if ($this->is_ended) {
            return 'archived';
        }

        return 'active';
    }

    /*
    |--------------------------------------------------------------------------
    | MEDIA LIBRARY - Spatie Configuration
    |--------------------------------------------------------------------------
    */

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->useFallbackUrl('/images/collection-placeholder.jpg')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('banner')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Register media conversions (optional - for thumbnails)
     */
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(800)
            ->height(800)
            ->sharpen(5)
            ->nonQueued();
    }
}
