<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

/**
 * Trait for searchable models
 * 
 * Enables full-text search across multiple fields
 */
trait Searchable
{
    /**
     * Scope to search across searchable fields
     */
    public function scopeSearch($query, ?string $keyword)
    {
        if (empty($keyword)) {
            return $query;
        }

        $searchableFields = $this->searchableFields ?? ['name'];

        return $query->where(function ($q) use ($keyword, $searchableFields) {
            foreach ($searchableFields as $field) {
                // Use ILIKE for case-insensitive search in PostgreSQL
                $q->orWhere($field, 'ILIKE', "%{$keyword}%");
            }
        });
    }

    /**
     * Scope to search exact match
     */
    public function scopeSearchExact($query, string $field, string $value)
    {
        return $query->where($field, $value);
    }
}
