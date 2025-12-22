<?php

declare(strict_types=1);

namespace Modules\Catalogue\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Enums\ProductStatus;

/**
 * Event déclenché quand un produit passe en rupture de stock
 *
 * Utilisé pour :
 * - Activer automatiquement la précommande
 * - Notifier les admins
 * - Mettre à jour les caches
 * - Logger l'événement
 */
class ProductOutOfStock
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Crée une nouvelle instance de l'événement
     */
    public function __construct(
        public readonly Product $product,
        public readonly int $previousStock = 0,
        public readonly ?string $reason = null
    ) {}

    /**
     * Vérifie si la précommande doit être activée automatiquement
     */
    public function shouldEnablePreorder(): bool
    {
        // Cast explicite pour aider Intelephense
        $status = $this->product->status;

        // Type guard pour être sûr que c'est bien un enum
        if (!$status instanceof ProductStatus) {
            return false;
        }

        return !$this->product->is_preorder_enabled
            && $status->canEnablePreorder();
    }

    /**
     * Obtenir le statut du produit (helper pour IDE)
     */
    private function getProductStatus(): ProductStatus
    {
        $status = $this->product->status;

        return $status instanceof ProductStatus
            ? $status
            : ProductStatus::from($status);
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
            'product_status' => $this->product->status->value,
            'previous_stock' => $this->previousStock,
            'current_stock' => $this->product->stock_quantity,
            'reason' => $this->reason,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
