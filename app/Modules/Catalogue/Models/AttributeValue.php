<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Modules\Core\Models\BaseModel;

/**
 * Attribute Value Model (e.g., "Red", "Large")
 * 
 * @property string $id
 * @property string $attribute_id
 * @property string $value
 * @property string $label
 */
class AttributeValue extends BaseModel
{
    protected $fillable = [
        'attribute_id',
        'value',
        'label',
        'color_code',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Attribute relationship
     */
    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
