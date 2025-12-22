<?php

declare(strict_types=1);

namespace Modules\Catalogue\Observers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Models\ProductVariation;

/**
 * Product Variation Observer
 *
 * Gère automatiquement :
 * - Génération automatique du SKU si non fourni
 * - Synchronisation du stock parent avec les variations
 * - Mise à jour du stock_status en fonction de la quantité
 */
class ProductVariationObserver
{
    /**
     * Avant la création d'une variation
     */
    public function creating(ProductVariation $variation): void
    {
        // Générer le SKU si non fourni
        if (empty($variation->sku)) {
            $variation->sku = $this->generateUniqueSku($variation->product_id);

            Log::info('Generated SKU for variation', [
                'variation_name' => $variation->variation_name,
                'sku' => $variation->sku,
            ]);
        }

        // Déterminer le stock_status automatiquement
        if (empty($variation->stock_status)) {
            $variation->stock_status = $variation->stock_quantity > 0 ? 'in_stock' : 'out_of_stock';
        }
    }

    /**
     * Après la création d'une variation
     */
    public function created(ProductVariation $variation): void
    {
        Log::info('Variation created', [
            'variation_id' => $variation->id,
            'product_id' => $variation->product_id,
            'sku' => $variation->sku,
        ]);

        // Mettre à jour le stock total du produit parent
        $this->syncParentProductStock($variation->product_id);
    }

    /**
     * Avant la mise à jour d'une variation
     */
    public function updating(ProductVariation $variation): void
    {
        // Mettre à jour stock_status si le stock a changé
        if ($variation->isDirty('stock_quantity')) {
            $oldStock = $variation->getOriginal('stock_quantity');
            $newStock = $variation->stock_quantity;

            $variation->stock_status = $newStock > 0 ? 'in_stock' : 'out_of_stock';

            Log::info('Variation stock updated', [
                'variation_id' => $variation->id,
                'product_id' => $variation->product_id,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'new_status' => $variation->stock_status,
            ]);
        }
    }

    /**
     * Après la mise à jour d'une variation
     */
    public function updated(ProductVariation $variation): void
    {
        // Si le stock ou is_active a changé, synchroniser le parent
        if ($variation->wasChanged(['stock_quantity', 'is_active'])) {
            $this->syncParentProductStock($variation->product_id);
        }
    }

    /**
     * Après la suppression (soft delete)
     */
    public function deleted(ProductVariation $variation): void
    {
        Log::info('Variation deleted', [
            'variation_id' => $variation->id,
            'product_id' => $variation->product_id,
        ]);

        // Synchroniser le stock parent
        $this->syncParentProductStock($variation->product_id);
    }

    /**
     * Après la restauration
     */
    public function restored(ProductVariation $variation): void
    {
        Log::info('Variation restored', [
            'variation_id' => $variation->id,
            'product_id' => $variation->product_id,
        ]);

        // Synchroniser le stock parent
        $this->syncParentProductStock($variation->product_id);
    }

    /**
     * Synchronise le stock du produit parent avec ses variations
     */
    protected function syncParentProductStock(string $productId): void
    {
        $product = Product::find($productId);

        if (!$product || !$product->is_variable) {
            return;
        }

        $oldStock = $product->stock_quantity;

        // Calculer le stock total de toutes les variations actives
        $totalStock = ProductVariation::where('product_id', $productId)
            ->where('is_active', true)
            ->sum('stock_quantity');

        // Mettre à jour le produit parent sans déclencher les observers
        $product->stock_quantity = $totalStock;
        $product->saveQuietly();

        Log::info('Parent product stock synced', [
            'product_id' => $productId,
            'old_stock' => $oldStock,
            'new_stock' => $totalStock,
        ]);
    }

    /**
     * Génère un SKU unique pour une variation
     */
    protected function generateUniqueSku(string $productId): string
    {
        $product = Product::find($productId);
        $baseSku = $product ? $product->sku : 'PROD';

        do {
            $sku = $baseSku . '-VAR-' . strtoupper(Str::random(6));
        } while (ProductVariation::where('sku', $sku)->exists());

        return $sku;
    }
}
