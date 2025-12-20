<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Modules\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Attribute Model (e.g., Color, Size, Material)
 *
 * @property string $id
 * @property string $name
 * @property string $code
 * @property string $type
 * @property bool $is_filterable
 * @property int $sort_order
 */
class Attribute extends BaseModel
{
    use SoftDeletes;

    /**
     * Fields that can be searched
     */
    protected $searchableFields = ['name', 'code'];

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_filterable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Attribute values (Red, Blue, XL, L, etc.)
     */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }

    /**
     * Products using this attribute
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_attributes')
            ->withPivot('attribute_value_id')
            ->withTimestamps();
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Only filterable attributes
     */
    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    /**
     * Check if attribute is color type
     */
    public function isColorType(): bool
    {
        return $this->type === 'color';
    }

    /**
     * Check if attribute is size type
     */
    public function isSizeType(): bool
    {
        return $this->type === 'size';
    }
}
