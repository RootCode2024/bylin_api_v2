<?php

declare(strict_types=1);

namespace Modules\Catalogue\Observers;

use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Enums\ProductStatus;
use Modules\Catalogue\Events\ProductOutOfStock;
use Modules\Catalogue\Events\ProductBackInStock;
use Modules\Catalogue\Events\ProductStatusChanged;
use Modules\Catalogue\Events\ProductStockUpdated;
use Modules\Catalogue\Events\PreorderEnabled;
use Modules\Catalogue\Events\PreorderDisabled;
use Illuminate\Support\Str;

/**
 * Product Observer
 *
 * Gère automatiquement les changements d'état des produits :
 * - Génération automatique du slug
 * - Gestion automatique de la précommande selon le stock
 * - Changement automatique du statut
 * - Dispatch des events appropriés
 */
class ProductObserver
{
    /**
     * Avant la création d'un produit
     */
    public function creating(Product $product): void
    {
        $product->slug = $this->generateUniqueSlug($product->name);
        $product->sku = $this->generateUniqueSku();
        $product->barcode = $this->generateUniqueBarcode();

        // Définir le statut initial selon le stock
        if ($product->stock_quantity === 0) {
            if ($product->status === ProductStatus::ACTIVE->value) {
                $product->status = ProductStatus::OUT_OF_STOCK->value;
            }

            // Activer la précommande AVANT la création
            if (!$product->is_preorder_enabled) {
                $this->enableAutomaticPreorder($product);
            }
        }
    }

    /**
     * Après la création d'un produit
     */
    public function created(Product $product): void {}

    /**
     * Avant la mise à jour d'un produit
     */
    public function updating(Product $product): void
    {
        // Vérifier si le stock a changé
        if ($product->isDirty('stock_quantity')) {
            $this->handleStockChange($product);
        }

        // Vérifier si le statut a changé
        if ($product->isDirty('status')) {
            $this->handleStatusChange($product);
        }

        // Mettre à jour le slug si le nom a changé
        if ($product->isDirty('name') && empty($product->getOriginal('slug'))) {
            $product->slug = $this->generateUniqueSlug($product->name, $product->id);
        }
    }

    /**
     * Après la mise à jour d'un produit
     */
    public function updated(Product $product): void
    {
        // Les events sont déjà dispatched dans handleStockChange et handleStatusChange
    }

    /**
     * Gère le changement de stock
     */
    protected function handleStockChange(Product $product): void
    {
        $oldStock = $product->getOriginal('stock_quantity');
        $newStock = $product->stock_quantity;

        // Dispatch ProductStockUpdated
        event(new ProductStockUpdated(
            product: $product,
            oldStock: $oldStock,
            newStock: $newStock,
            operation: 'update'
        ));

        // Stock passe de > 0 à 0 (Rupture de stock)
        if ($oldStock > 0 && $newStock === 0) {
            $this->handleOutOfStock($product, $oldStock);
        }
        // Stock passe de 0 à > 0 (Retour en stock)
        elseif ($oldStock === 0 && $newStock > 0) {
            $this->handleBackInStock($product, $newStock);
        }
    }

    /**
     * Gère la rupture de stock
     */
    protected function handleOutOfStock(Product $product, int $previousStock): void
    {
        // Changer le statut en OUT_OF_STOCK si actuellement ACTIVE
        if ($product->status === ProductStatus::ACTIVE->value) {
            $oldStatus = ProductStatus::from($product->status);
            $product->status = ProductStatus::OUT_OF_STOCK->value;

            // Dispatch ProductStatusChanged (sans sauvegarder encore)
            event(new ProductStatusChanged(
                product: $product,
                oldStatus: $oldStatus,
                newStatus: ProductStatus::OUT_OF_STOCK
            ));
        }

        // Dispatch ProductOutOfStock
        event(new ProductOutOfStock(
            product: $product,
            previousStock: $previousStock,
            reason: 'stock_depleted'
        ));

        // Activer la précommande automatique si pas déjà activée manuellement
        if (!$product->is_preorder_enabled) {
            $this->enableAutomaticPreorder($product);
        }
    }

    /**
     * Gère le retour en stock
     */
    protected function handleBackInStock(Product $product, int $newStock): void
    {
        // Dispatch ProductBackInStock
        event(new ProductBackInStock(
            product: $product,
            newStock: $newStock,
            previousStock: 0
        ));

        // Si la précommande était automatique, la désactiver
        if ($product->is_preorder_enabled && $product->preorder_auto_enabled) {
            $this->disableAutomaticPreorder($product);
        }

        // Changer le statut en ACTIVE si actuellement OUT_OF_STOCK ou PREORDER
        if (in_array($product->status, [ProductStatus::OUT_OF_STOCK->value, ProductStatus::PREORDER->value])) {
            $oldStatus = ProductStatus::from($product->status);
            $product->status = ProductStatus::ACTIVE->value;

            event(new ProductStatusChanged(
                product: $product,
                oldStatus: $oldStatus,
                newStatus: ProductStatus::ACTIVE
            ));
        }
    }

    /**
     * Gère le changement de statut
     */
    protected function handleStatusChange(Product $product): void
    {
        $oldStatus = ProductStatus::from($product->getOriginal('status'));
        $newStatus = ProductStatus::from($product->status);

        // Dispatch ProductStatusChanged
        event(new ProductStatusChanged(
            product: $product,
            oldStatus: $oldStatus,
            newStatus: $newStatus
        ));

        // Si le nouveau statut est PREORDER et pas encore activé
        if ($newStatus === ProductStatus::PREORDER && !$product->is_preorder_enabled) {
            $this->enableAutomaticPreorder($product);
        }
    }

    /**
     * Active la précommande automatique
     */
    protected function enableAutomaticPreorder(Product $product): void
    {
        $product->is_preorder_enabled = true;
        $product->preorder_auto_enabled = true;
        $product->preorder_enabled_at = now();

        // Dispatch PreorderEnabled
        event(new PreorderEnabled(
            product: $product,
            isAutomatic: true
        ));
    }

    /**
     * Désactive la précommande automatique
     */
    protected function disableAutomaticPreorder(Product $product): void
    {
        $product->is_preorder_enabled = false;
        $product->preorder_auto_enabled = false;

        // Dispatch PreorderDisabled
        event(new PreorderDisabled(
            product: $product,
            isAutomatic: true,
            reason: 'back_in_stock'
        ));
    }

    /**
     * Génère un slug unique
     */
    protected function generateUniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    /**
     * Vérifie si un slug existe déjà
     */
    protected function slugExists(string $slug, ?string $ignoreId = null): bool
    {
        $query = Product::where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Génère un SKU unique
     */
    protected function generateUniqueSku(): string
    {
        do {
            $sku = 'PROD-' . strtoupper(Str::random(8));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    protected function generateUniqueBarcode(): string
    {
        do {
            // 13 chiffres (compatible EAN / Code128)
            $barcode = (string) random_int(1000000000000, 9999999999999);
        } while (Product::where('barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Avant la suppression (soft delete)
     */
    public function deleting(Product $product): void
    {
        // Si en précommande, désactiver avant suppression
        if ($product->is_preorder_enabled) {
            event(new PreorderDisabled(
                product: $product,
                isAutomatic: false,
                reason: 'product_deleted'
            ));
        }
    }

    /**
     * Après la restauration
     */
    public function restored(Product $product): void
    {
        // Si le produit avait la précommande avant suppression et stock = 0
        if ($product->stock_quantity === 0) {
            $this->enableAutomaticPreorder($product);
            $product->saveQuietly(); // Save sans déclencher les observers à nouveau
        }
    }
}
