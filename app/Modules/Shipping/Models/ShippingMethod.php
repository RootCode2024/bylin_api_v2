<?php

declare(strict_types=1);

namespace Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;

class ShippingMethod extends BaseModel
{
    use HasUuids, SoftDeletes;

    protected $table = 'shipping_methods';

    protected $fillable = [
        'name',
        'code',
        'description',
        'carrier',
        'rate_calculation',
        'base_cost',
        'estimated_delivery_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'rate_calculation' => 'array',
        'base_cost' => 'decimal:2',
        'estimated_delivery_days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the shipments using this method
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Scope for active shipping methods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Scope by code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Calculate shipping cost based on order details
     */
    public function calculateCost(array $orderDetails): float
    {
        $cost = (float) $this->base_cost;

        if (!$this->rate_calculation) {
            return $cost;
        }

        // Example rate calculation logic
        // This can be customized based on your business rules
        $rules = $this->rate_calculation;

        // Weight based
        if (isset($rules['per_kg']) && isset($orderDetails['weight'])) {
            $cost += $rules['per_kg'] * $orderDetails['weight'];
        }

        // Distance based
        if (isset($rules['per_km']) && isset($orderDetails['distance'])) {
            $cost += $rules['per_km'] * $orderDetails['distance'];
        }

        // Item count based
        if (isset($rules['per_item']) && isset($orderDetails['item_count'])) {
            $cost += $rules['per_item'] * $orderDetails['item_count'];
        }

        // Free shipping threshold
        if (isset($rules['free_shipping_threshold']) && isset($orderDetails['subtotal'])) {
            if ($orderDetails['subtotal'] >= $rules['free_shipping_threshold']) {
                return 0;
            }
        }

        // Maximum cost cap
        if (isset($rules['max_cost']) && $cost > $rules['max_cost']) {
            $cost = $rules['max_cost'];
        }

        return round($cost, 2);
    }

    /**
     * Get estimated delivery date
     */
    public function getEstimatedDeliveryDate(): ?\Carbon\Carbon
    {
        if (!$this->estimated_delivery_days) {
            return null;
        }

        return now()->addDays($this->estimated_delivery_days);
    }
}
