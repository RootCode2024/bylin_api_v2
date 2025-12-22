<?php

declare(strict_types=1);

namespace Modules\Catalogue\Services;

use Carbon\Carbon;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Events\PreorderEnabled;
use Modules\Catalogue\Events\PreorderDisabled;
use Modules\Core\Exceptions\BusinessException;
use Modules\Core\Services\BaseService;

/**
 * Preorder Service
 *
 * Handles business logic for product preorders and automatic activation
 *
 * ✅ NOMS UNIFORMISÉS :
 * - is_preorder_enabled (précommande activée)
 * - preorder_auto_enabled (activation automatique)
 * - preorder_available_date (date de disponibilité)
 */
class PreorderService extends BaseService
{
    /**
     * Enable preorder for a product (MANUAL)
     */
    public function enablePreorder(
        string $productId,
        ?Carbon $availableDate = null,
        ?int $limit = null,
        ?string $message = null,
        ?string $terms = null
    ): Product {
        return $this->transaction(function () use ($productId, $availableDate, $limit, $message, $terms) {
            $product = Product::findOrFail($productId);

            $product->update([
                'is_preorder_enabled' => true,
                'preorder_auto_enabled' => false, // Manual activation
                'preorder_available_date' => $availableDate,
                'preorder_limit' => $limit,
                'preorder_message' => $message,
                'preorder_terms' => $terms,
                'preorder_enabled_at' => now(),
            ]);

            $this->logInfo('Preorder enabled manually', [
                'product_id' => $productId,
                'available_date' => $availableDate?->toDateString(),
            ]);

            // Dispatch event
            event(new PreorderEnabled(
                product: $product,
                isAutomatic: false,
                availabilityDate: $availableDate,
                reason: 'manual'
            ));

            return $product->fresh();
        });
    }

    /**
     * Disable preorder for a product
     */
    public function disablePreorder(string $productId, string $reason = 'manual'): Product
    {
        return $this->transaction(function () use ($productId, $reason) {
            $product = Product::findOrFail($productId);

            if (!$product->is_preorder_enabled) {
                throw new BusinessException('Preorder is not enabled for this product');
            }

            $wasAutomatic = $product->preorder_auto_enabled;

            $product->update([
                'is_preorder_enabled' => false,
                'preorder_auto_enabled' => false,
                'preorder_available_date' => null,
            ]);

            $this->logInfo('Preorder disabled', [
                'product_id' => $productId,
                'preorder_count' => $product->preorder_count,
                'was_automatic' => $wasAutomatic,
            ]);

            event(new PreorderDisabled(
                product: $product,
                isAutomatic: $wasAutomatic,
                reason: $reason
            ));

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
        if (!config('catalogue.preorder.auto_enable_on_out_of_stock', true)) {
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
     * Enable automatic preorder (called by Observer)
     */
    public function enableAutoPreorder(Product $product): void
    {
        $defaultWaitDays = config('catalogue.preorder.default_wait_period_days', 30);
        $availableDate = now()->addDays($defaultWaitDays);

        $product->update([
            'is_preorder_enabled' => true,
            'preorder_auto_enabled' => true, // ✅ UNIFORMISÉ
            'preorder_available_date' => $availableDate,
            'preorder_enabled_at' => now(),
        ]);

        $this->logInfo('Auto preorder enabled', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'available_date' => $availableDate->toDateString(),
        ]);

        event(new PreorderEnabled(
            product: $product,
            isAutomatic: true,
            availabilityDate: $availableDate,
            reason: 'out_of_stock'
        ));
    }

    /**
     * Disable automatic preorder when stock returns
     */
    public function disableAutoPreorder(Product $product): void
    {
        $product->update([
            'is_preorder_enabled' => false,
            'preorder_auto_enabled' => false, // ✅ UNIFORMISÉ
            'preorder_available_date' => null,
        ]);

        $this->logInfo('Auto preorder disabled - stock available', [
            'product_id' => $product->id,
            'new_stock' => $product->stock_quantity,
            'preorder_count' => $product->preorder_count,
        ]);

        event(new PreorderDisabled(
            product: $product,
            isAutomatic: true,
            reason: 'back_in_stock'
        ));
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
            'is_automatic' => $product->preorder_auto_enabled,
            'available_date' => $product->preorder_available_date?->toDateString(),
            'limit' => $product->preorder_limit,
            'current_count' => $product->preorder_count,
            'spots_remaining' => $product->preorder_limit
                ? $product->preorder_limit - $product->preorder_count
                : null,
            'can_preorder' => $this->canPreorder($product),
            'message' => $product->preorder_message,
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
