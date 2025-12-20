<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Modules\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Attribute Value Model (e.g., "Red", "Large", "Cotton")
 *
 * @property string $id
 * @property string $attribute_id
 * @property string $value
 * @property string $label
 * @property string|null $color_code
 * @property int $sort_order
 */
class AttributeValue extends BaseModel
{
    use SoftDeletes;

    /**
     * Fields that can be searched
     */
    protected $searchableFields = ['value', 'label'];

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
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Attribute relationship
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Check if this value has a color code
     */
    public function hasColorCode(): bool
    {
        return !empty($this->color_code);
    }

    /**
     * Get display name (label or value)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->label ?? $this->value;
    }
}
