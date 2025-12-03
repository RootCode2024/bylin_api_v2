<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

/**
 * Trait for models with status field
 * 
 * Provides standardized status management
 */
trait HasStatus
{
    /**
     * Get the available statuses
     */
    abstract public function getAvailableStatuses(): array;

    /**
     * Check if the current status is the given status
     */
    public function isStatus(string $status): bool
    {
        return $this->status === $status;
    }

    /**
     * Check if the model is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the model is inactive
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Activate the model
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Deactivate the model
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }

    /**
     * Scope to filter by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter active records
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter inactive records
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}
