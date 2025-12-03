<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Models\ProductVariation;
use Modules\Core\Services\BaseService;
use Modules\Inventory\Models\StockMovement;
use Modules\Notification\Services\NotificationService;

class InventoryService extends BaseService
{
    /**
     * Check if stock is available
     */
    public function checkStock(string $productId, int $quantity, ?string $variationId = null): bool
    {
        if ($variationId) {
            $variation = ProductVariation::findOrFail($variationId);
            return $variation->stock_quantity >= $quantity;
        }

        $product = Product::findOrFail($productId);
        
        // If inventory tracking is disabled, always return true
        if (!$product->track_inventory) {
            return true;
        }

        return $product->stock_quantity >= $quantity;
    }

    /**
     * Reserve stock (decrement) for an order
     */
    public function reserveStock(string $productId, int $quantity, ?string $variationId = null, ?string $orderId = null): void
    {
        // This creates a movement which automatically updates the product/variation stock
        StockMovement::recordSale($productId, $quantity, $variationId, $orderId);

        // Check for low stock alert
        $this->checkLowStockAlert($productId, $variationId);
    }

    /**
     * Release stock (increment) for a cancelled/returned order
     */
    public function releaseStock(string $productId, int $quantity, ?string $variationId = null, ?string $orderId = null): void
    {
        StockMovement::recordReturn($productId, $quantity, $variationId, $orderId);
    }

    /**
     * Adjust stock manually
     */
    public function adjustStock(string $productId, int $quantity, string $reason, ?string $variationId = null, ?string $notes = null): void
    {
        $type = $quantity > 0 ? StockMovement::TYPE_IN : StockMovement::TYPE_OUT;
        
        StockMovement::createMovement([
            'product_id' => $productId,
            'variation_id' => $variationId,
            'type' => $type,
            'reason' => $reason,
            'quantity' => abs($quantity), // Quantity is always positive in DB, type determines sign
            'notes' => $notes,
        ]);
    }

    /**
     * Check and trigger low stock alert
     */
    protected function checkLowStockAlert(string $productId, ?string $variationId = null): void
    {
        $product = Product::findOrFail($productId);
        
        if (!$product->track_inventory) {
            return;
        }

        $currentStock = $product->stock_quantity;
        $itemName = $product->name;

        if ($variationId) {
            $variation = ProductVariation::findOrFail($variationId);
            $currentStock = $variation->stock_quantity;
            $itemName .= " ({$variation->variation_name})";
        }

        if ($currentStock <= $product->low_stock_threshold) {
            // Trigger notification
            // NotificationService::sendAdminAlert(...)
            // This would be implemented when NotificationService is ready
        }
    }
}
