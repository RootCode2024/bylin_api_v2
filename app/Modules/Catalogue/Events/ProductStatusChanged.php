<?php

declare(strict_types=1);

namespace Modules\Catalogue\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Enums\ProductStatus;

/**
 * Event déclenché quand le statut d'un produit change
 *
 * Utilisé pour :
 * - Logger les changements de statut
 * - Déclencher des workflows automatiques
 * - Mettre à jour les indexes de recherche
 * - Invalider les caches
 */
class ProductStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Crée une nouvelle instance de l'événement
     */
    public function __construct(
        public readonly Product $product,
        public readonly ProductStatus $oldStatus,
        public readonly ProductStatus $newStatus,
        public readonly ?string $changedBy = null
    ) {}

    /**
     * Vérifie si le produit est devenu visible
     */
    public function becameVisible(): bool
    {
        return !$this->oldStatus->isVisible() && $this->newStatus->isVisible();
    }

    /**
     * Vérifie si le produit est devenu invisible
     */
    public function becameInvisible(): bool
    {
        return $this->oldStatus->isVisible() && !$this->newStatus->isVisible();
    }

    /**
     * Vérifie si le produit est devenu disponible à l'achat
     */
    public function becameAvailableForPurchase(): bool
    {
        return !$this->oldStatus->isAvailableForPurchase()
            && $this->newStatus->isAvailableForPurchase();
    }

    /**
     * Vérifie si le produit n'est plus disponible à l'achat
     */
    public function becameUnavailableForPurchase(): bool
    {
        return $this->oldStatus->isAvailableForPurchase()
            && !$this->newStatus->isAvailableForPurchase();
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
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'changed_by' => $this->changedBy,
            'became_visible' => $this->becameVisible(),
            'became_available' => $this->becameAvailableForPurchase(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
