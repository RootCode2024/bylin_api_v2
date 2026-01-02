<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Spatie\MediaLibrary\HasMedia;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\Searchable;
use Modules\Catalogue\Models\Product;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends BaseModel implements HasMedia
{
    use Searchable, InteractsWithMedia, HasUuids, SoftDeletes;

    protected $searchableFields = ['name', 'description'];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'website',
        'is_active',
        'sort_order',
        'meta_data',
        'is_bylin_brand',
    ];

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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function getLogoUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('logo');

        return $media?->getUrl();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    public function isBylinBrand(): bool
    {
        return $this->is_bylin_brand === true;
    }

    public function scopeBylin($query)
    {
        return $query->where('is_bylin_brand', true);
    }

    public function scopeNonBylin($query)
    {
        return $query->where('is_bylin_brand', false);
    }

    public function getActiveProductsCountAttribute(): int
    {
        return $this->products()->active()->count();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeWithProducts($query)
    {
        return $query->has('products');
    }
}
