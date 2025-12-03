<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Base model for all application models
 * 
 * Provides common functionality:
 * - UUID primary keys
 * - Soft deletes
 * - Automatic timestamp management
 * - Common scopes
 */
abstract class BaseModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The primary key type
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing
     */
    public $incrementing = false;

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Scope a query to only include active records
     */
    public function scopeActive($query)
    {
        return method_exists($this, 'getIsActiveAttribute')
            ? $query->where('is_active', true)
            : $query;
    }

    /**
     * Scope a query to search by keyword
     */
    public function scopeSearch($query, ?string $keyword)
    {
        if (empty($keyword)) {
            return $query;
        }

        $searchable = $this->searchableFields ?? ['name'];

        return $query->where(function ($q) use ($keyword, $searchable) {
            foreach ($searchable as $field) {
                $q->orWhere($field, 'ILIKE', "%{$keyword}%");
            }
        });
    }

    /**
     * Scope a query to order by latest
     */
    public function scopeLatest($query, string $column = 'created_at')
    {
        return $query->orderBy($column, 'desc');
    }
}
