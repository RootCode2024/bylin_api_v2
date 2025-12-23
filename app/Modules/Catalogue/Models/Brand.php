<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\Searchable;
use Illuminate\Support\Facades\Storage;

/**
 * Brand Model
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property bool $is_active
 * @property string|null $logo_url
 */
class Brand extends BaseModel
{
    use Searchable;

    protected $searchableFields = ['name', 'description'];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'website',
        'is_active',
        'sort_order',
        'meta_data',
        'is_bylin_brand',
    ];

    // Ajouter logo_url aux attributs retournés automatiquement
    protected $appends = ['logo_url'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'meta_data' => 'array',
            'is_bylin_brand' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the full URL for the logo
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        // Si le logo est déjà une URL complète
        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        // Destion de l'URL complète depuis le storage
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        return $disk->url($this->logo);
    }

    /**
     * Products relationship
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Collections associated with this brand (Bylin only)
     */
    public function collections()
    {
        return $this->hasMany(Collection::class);
    }

    /**
     * Check if this is the Bylin brand
     */
    public function isBylinBrand(): bool
    {
        return $this->is_bylin_brand === true;
    }

    /**
     * Scope for Bylin brand only
     */
    public function scopeBylin($query)
    {
        return $query->where('is_bylin_brand', true);
    }

    /**
     * Scope for non-Bylin brands
     */
    public function scopeNonBylin($query)
    {
        return $query->where('is_bylin_brand', false);
    }

    /**
     * Get active products count
     */
    public function getActiveProductsCountAttribute(): int
    {
        return $this->products()->active()->count();
    }

    /**
     * Scope for active brands
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for brands with products
     */
    public function scopeWithProducts($query)
    {
        return $query->has('products');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);
    }
}
