<?php

declare(strict_types=1);

namespace Modules\Catalogue\Events;

use Modules\Catalogue\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Event dispatché quand un produit revient en stock
 *
 * Utilisé pour :
 * - Notifier les clients en liste d'attente
 * - Réactiver les campagnes marketing
 * - Logger le retour en stock
 * - Synchroniser avec le système ERP
 */
class ProductBackInStock
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Product $product Le produit qui revient en stock
     * @param int $newStock La nouvelle quantité en stock
     * @param int $previousStock L'ancienne quantité (généralement 0)
     */
    public function __construct(
        public readonly Product $product,
        public readonly int $newStock,
        public readonly int $previousStock = 0,
    ) {}

    /**
     * Obtenir les données pour les notifications
     */
    public function getNotificationData(): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_slug' => $this->product->slug,
            'new_stock' => $this->newStock,
            'previous_stock' => $this->previousStock,
            'difference' => $this->newStock - $this->previousStock,
            'occurred_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Vérifier si c'est un retour significatif
     */
    public function isSignificantRestock(): bool
    {
        // Retour significatif si > 10 unités ou 100% d'augmentation
        return $this->newStock >= 10 ||
            ($this->previousStock > 0 && $this->newStock >= $this->previousStock * 2);
    }

    /**
     * Obtenir le message de log
     */
    public function getLogMessage(): string
    {
        return sprintf(
            'Product [%s] back in stock: %d → %d units (+%d)',
            $this->product->name,
            $this->previousStock,
            $this->newStock,
            $this->newStock - $this->previousStock
        );
    }
}
