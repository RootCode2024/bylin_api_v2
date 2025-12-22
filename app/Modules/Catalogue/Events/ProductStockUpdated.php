<?php

declare(strict_types=1);

namespace Modules\Catalogue\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Catalogue\Models\Product;

/**
 * Event déclenché quand le stock d'un produit est mis à jour
 *
 * Utilisé pour :
 * - Déclencher des alertes de stock bas
 * - Mettre à jour les caches
 * - Logger les mouvements de stock
 * - Déclencher ProductOutOfStock si stock = 0
 * - Déclencher ProductBackInStock si retour en stock
 */
class ProductStockUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Crée une nouvelle instance de l'événement
     */
    public function __construct(
        public readonly Product $product,
        public readonly int $oldStock,
        public readonly int $newStock,
        public readonly string $operation = 'set',
        public readonly ?string $reason = null
    ) {}

    /**
     * Vérifie si le stock est passé de > 0 à 0
     */
    public function wentOutOfStock(): bool
    {
        return $this->oldStock > 0 && $this->newStock === 0;
    }

    /**
     * Vérifie si le stock est passé de 0 à > 0
     */
    public function cameBackInStock(): bool
    {
        return $this->oldStock === 0 && $this->newStock > 0;
    }

    /**
     * Vérifie si le stock est en alerte basse
     */
    public function isLowStock(): bool
    {
        return $this->newStock > 0
            && $this->newStock <= $this->product->low_stock_threshold;
    }

    /**
     * Vérifie si le stock a augmenté
     */
    public function stockIncreased(): bool
    {
        return $this->newStock > $this->oldStock;
    }

    /**
     * Vérifie si le stock a diminué
     */
    public function stockDecreased(): bool
    {
        return $this->newStock < $this->oldStock;
    }

    /**
     * Retourne la différence de stock
     */
    public function getStockDifference(): int
    {
        return $this->newStock - $this->oldStock;
    }

    /**
     * Retourne les données de l'événement pour logging
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'old_stock' => $this->oldStock,
            'new_stock' => $this->newStock,
            'difference' => $this->getStockDifference(),
            'operation' => $this->operation,
            'reason' => $this->reason,
            'went_out_of_stock' => $this->wentOutOfStock(),
            'came_back_in_stock' => $this->cameBackInStock(),
            'is_low_stock' => $this->isLowStock(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
