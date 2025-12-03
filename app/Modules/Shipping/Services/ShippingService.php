<?php

declare(strict_types=1);

namespace Modules\Shipping\Services;

use Modules\Cart\Models\Cart;
use Modules\Core\Services\BaseService;
use Modules\Shipping\Models\ShippingMethod;

class ShippingService extends BaseService
{
    /**
     * Calculate shipping cost for a cart
     */
    public function calculateCost(Cart $cart, ?string $shippingMethodId = null, ?array $address = null): float
    {
        if ($cart->items->isEmpty()) {
            return 0.0;
        }

        // If no method selected, return 0 or default method cost
        if (!$shippingMethodId) {
            return 0.0;
        }

        $method = ShippingMethod::findOrFail($shippingMethodId);

        // Prepare order details for calculation
        $details = [
            'subtotal' => $cart->subtotal,
            'item_count' => $cart->items->sum('quantity'),
            'weight' => 0, // TODO: Sum weight from products
            'distance' => 0, // TODO: Calculate distance from address
        ];

        return $method->calculateCost($details);
    }

    /**
     * Get available shipping methods for an address
     */
    public function getAvailableMethods(?array $address = null): \Illuminate\Database\Eloquent\Collection
    {
        // Filter methods based on address (zone) if needed
        return ShippingMethod::active()->get();
    }
}
