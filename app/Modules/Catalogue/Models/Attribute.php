<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Modules\Core\Models\BaseModel;

/**
 * Attribute Model (e.g., Color, Size)
 * 
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $type
 */
class Attribute extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
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
     * Attribute values
     */
    public function values()
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }

    /**
     * Products using this attribute
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_attributes')
            ->withPivot('attribute_value_id')
            ->withTimestamps();
    }
}
