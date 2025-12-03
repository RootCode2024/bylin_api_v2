<?php

declare(strict_types=1);

namespace Modules\Catalogue\Services;

use Carbon\Carbon;
use Modules\Catalogue\Models\Product;
use Modules\Core\Exceptions\BusinessException;
use Modules\Core\Services\BaseService;

/**
 * Preorder Service
 * 
 * Handles business logic for product preorders and automatic activation
 */
class PreorderService extends BaseService
{
    /**
     * Enable preorder for a product
     */
    public function enablePreorder(
        string $productId,
        ?Carbon $availableDate = null,
        ?int $limit = null,
        ?string $terms = null
    ): Product {
        return $this->transaction(function () use ($productId, $availableDate, $limit, $terms) {
            $product = Product::findOrFail($productId);

            $product->update([
                'is_preorder_enabled' => true,
                'is_preorder_auto' => false, // Manual activation
                'preorder_available_date' => $availableDate,
                'preorder_limit' => $limit,
                'preorder_terms' => $terms,
            ]);

            $this->logInfo('Preorder enabled manually', [
                'product_id' => $productId,
                'available_date' => $availableDate?->toDateString(),
            ]);

            // Dispatch event
            event(new \Modules\Catalogue\Events\PreorderEnabled($product));

            return $product->fresh();
        });
    }

    /**
     * Disable preorder for a product
     */
    public function disablePreorder(string $productId): Product
    {
        return $this->transaction(function () use ($productId) {
            $product = Product::findOrFail($productId);

            if (!$product->is_preorder_enabled) {
                throw new BusinessException('Preorder is not enabled for this product');
            }

            $product->update([
                'is_preorder_enabled' => false,
                'is_preorder_auto' => false,
                'preorder_available_date' => null,
            ]);

            $this->logInfo('Preorder disabled', [
                'product_id' => $productId,
                'preorder_count' => $product->preorder_count,
            ]);

            return $product->fresh();
        });
    }

    /**
     * Check and enable automatic preorder if stock is 0
     */
    public function checkAutoPreorder(string $productId): bool
    {
        $product = Product::findOrFail($productId);

        // Skip if preorder auto is disabled globally
        if (!config('ecommerce.preorder.auto_enable_on_out_of_stock', true)) {
            return false;
        }

        // Skip if already in preorder (manual or auto)
        if ($product->is_preorder_enabled) {
            return false;
        }

        // Check if stock is 0
        if ($product->stock_quantity === 0) {
            $this->enableAutoPreorder($product);
            return true;
        }

        return false;
    }

    /**
     * Enable automatic preorder
     */
    protected function enableAutoPreorder(Product $product): void
    {
        $defaultWaitDays = config('ecommerce.preorder.default_wait_period_days', 30);
        $availableDate = now()->addDays($defaultWaitDays);

        $product->update([
            'is_preorder_enabled' => true,
            'is_preorder_auto' => true,
            'preorder_available_date' => $availableDate,
            'status' => 'out_of_stock', // Optional: change status
        ]);

        $this->logInfo('Auto preorder enabled', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'available_date' => $availableDate->toDateString(),
        ]);

        event(new \Modules\Catalogue\Events\PreorderEnabled($product));
    }

    /**
     * Update stock and check if preorder should be disabled
     */
    public function updateStockAndCheckPreorder(string $productId, int $newStock): Product
    {
        return $this->transaction(function () use ($productId, $newStock) {
            $product = Product::findOrFail($productId);
            $oldStock = $product->stock_quantity;

            // Update stock
            $product->update(['stock_quantity' => $newStock]);

            // If stock was 0 and now > 0, check auto preorder
            if ($oldStock === 0 && $newStock === 0) {
                $this->checkAutoPreorder($productId);
            }
            // If stock becomes > 0 and auto preorder was enabled, disable it
            elseif ($newStock > 0 && $product->is_preorder_auto) {
                $this->disableAutoPreorder($product);
            }

            return $product->fresh();
        });
    }

    /**
     * Disable automatic preorder when stock returns
     */
    protected function disableAutoPreorder(Product $product): void
    {
        $product->update([
            'is_preorder_enabled' => false,
            'is_preorder_auto' => false,
            'preorder_available_date' => null,
            'status' => 'active', // Reactivate product
        ]);

        // Notify preorder customers
        if ($product->preorder_count > 0) {
            event(new \Modules\Catalogue\Events\PreorderAvailable($product));
        }

        $this->logInfo('Auto preorder disabled - stock available', [
            'product_id' => $product->id,
            'new_stock' => $product->stock_quantity,
            'preorder_count' => $product->preorder_count,
        ]);
    }

    /**
     * Check if product can be preordered
     */
    public function canPreorder(Product $product): bool
    {
        if (!$product->is_preorder_enabled) {
            return false;
        }

        // Check limit
        if ($product->preorder_limit !== null && $product->preorder_count >= $product->preorder_limit) {
            return false;
        }

        return true;
    }

    /**
     * Increment preorder count
     */
    public function incrementPreorderCount(string $productId, int $quantity = 1): void
    {
        $product = Product::findOrFail($productId);
        $product->increment('preorder_count', $quantity);

        $this->logInfo('Preorder count incremented', [
            'product_id' => $productId,
            'quantity' => $quantity,
            'new_count' => $product->preorder_count,
        ]);
    }

    /**
     * Get product preorder info
     */
    public function getPreorderInfo(string $productId): array
    {
        $product = Product::findOrFail($productId);

        return [
            'is_preorder_enabled' => $product->is_preorder_enabled,
            'is_auto' => $product->is_preorder_auto,
            'available_date' => $product->preorder_available_date?->toDateString(),
            'limit' => $product->preorder_limit,
            'current_count' => $product->preorder_count,
            'spots_remaining' => $product->preorder_limit 
                ? $product->preorder_limit - $product->preorder_count 
                : null,
            'can_preorder' => $this->canPreorder($product),
            'terms' => $product->preorder_terms,
        ];
    }

    /**
     * Batch check all products for auto preorder
     */
    public function batchCheckAutoPreorder(): int
    {
        $enabledCount = 0;

        $outOfStockProducts = Product::where('stock_quantity', 0)
            ->where('is_preorder_enabled', false)
            ->where('track_inventory', true)
            ->get();

        foreach ($outOfStockProducts as $product) {
            if ($this->checkAutoPreorder($product->id)) {
                $enabledCount++;
            }
        }

        $this->logInfo('Batch auto preorder check completed', [
            'checked' => $outOfStockProducts->count(),
            'enabled' => $enabledCount,
        ]);

        return $enabledCount;
    }
}
