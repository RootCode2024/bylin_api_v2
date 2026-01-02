<?php

declare(strict_types=1);

namespace Modules\Catalogue\Events;

use Modules\Catalogue\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Event dispatché quand la précommande est activée pour un produit
 *
 * Utilisé pour :
 * - Notifier les clients intéressés
 * - Afficher le badge "Précommande" sur le site
 * - Ajuster la logistique et les commandes fournisseurs
 * - Logger l'activation
 */
class PreorderEnabled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Product $product Le produit en précommande
     * @param bool $isAutomatic Si l'activation est automatique (rupture stock) ou manuelle
     * @param \DateTimeInterface|null $availabilityDate Date de disponibilité prévue
     * @param string|null $reason Raison de l'activation (out_of_stock, upcoming_product, etc.)
     */
    public function __construct(
        public readonly Product $product,
        public readonly bool $isAutomatic = false,
        public readonly ?\DateTimeInterface $availabilityDate = null,
        public readonly ?string $reason = null,
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
            'is_automatic' => $this->isAutomatic,
            'availability_date' => $this->availabilityDate?->format('d/m/Y'),
            'reason' => $this->reason ?? ($this->isAutomatic ? 'out_of_stock' : 'manual'),
            'current_stock' => $this->product->stock_quantity,
            'activated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Obtenir le type d'activation
     */
    public function getActivationType(): string
    {
        if ($this->isAutomatic) {
            return 'automatic';
        }

        return $this->product->stock_quantity > 0 ? 'planned' : 'manual';
    }

    /**
     * Vérifier si une date de disponibilité est définie
     */
    public function hasAvailabilityDate(): bool
    {
        return $this->availabilityDate !== null;
    }

    /**
     * Obtenir le message de log
     */
    public function getLogMessage(): string
    {
        $type = $this->isAutomatic ? 'automatic' : 'manual';
        $dateInfo = $this->availabilityDate
            ? sprintf(' (available: %s)', $this->availabilityDate->format('d/m/Y'))
            : '';

        return sprintf(
            'Preorder enabled for product [%s] - Type: %s%s - Stock: %d',
            $this->product->name,
            $type,
            $dateInfo,
            $this->product->stock_quantity
        );
    }

    /**
     * Vérifier si c'est une activation critique (rupture inattendue)
     */
    public function isCritical(): bool
    {
        return $this->isAutomatic &&
            $this->product->stock_quantity === 0 &&
            $this->reason === 'out_of_stock';
    }
}
