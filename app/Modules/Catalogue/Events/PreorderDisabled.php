<?php

declare(strict_types=1);

namespace Modules\Catalogue\Events;

use Modules\Catalogue\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Event dispatché quand la précommande est désactivée pour un produit
 *
 * Utilisé pour :
 * - Notifier les clients qui ont précommandé
 * - Retirer le badge "Précommande" du site
 * - Finaliser les commandes en précommande
 * - Logger la désactivation
 */
class PreorderDisabled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Product $product Le produit dont la précommande est désactivée
     * @param bool $isAutomatic Si la désactivation est automatique (retour stock) ou manuelle
     * @param string|null $reason Raison de la désactivation (back_in_stock, cancelled, product_deleted, etc.)
     */
    public function __construct(
        public readonly Product $product,
        public readonly bool $isAutomatic = false,
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
            'reason' => $this->reason ?? ($this->isAutomatic ? 'back_in_stock' : 'manual'),
            'current_stock' => $this->product->stock_quantity,
            'disabled_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Obtenir le type de désactivation
     */
    public function getDisableType(): string
    {
        if ($this->reason) {
            return $this->reason;
        }

        if ($this->isAutomatic) {
            return $this->product->stock_quantity > 0 ? 'back_in_stock' : 'system';
        }

        return 'manual';
    }

    /**
     * Vérifier si c'est une désactivation positive (retour en stock)
     */
    public function isPositiveDisable(): bool
    {
        return $this->reason === 'back_in_stock' ||
            ($this->isAutomatic && $this->product->stock_quantity > 0);
    }

    /**
     * Vérifier si c'est une désactivation négative (annulation, suppression)
     */
    public function isNegativeDisable(): bool
    {
        return in_array($this->reason, ['cancelled', 'product_deleted', 'discontinued']);
    }

    /**
     * Obtenir le message de log
     */
    public function getLogMessage(): string
    {
        $type = $this->isAutomatic ? 'automatic' : 'manual';
        $reason = $this->reason ?? 'unknown';

        return sprintf(
            'Preorder disabled for product [%s] - Type: %s - Reason: %s - Stock: %d',
            $this->product->name,
            $type,
            $reason,
            $this->product->stock_quantity
        );
    }

    /**
     * Vérifier s'il y a des précommandes en attente à traiter
     */
    public function hasPendingPreorders(): bool
    {
        // Cette méthode devra être implémentée avec la logique de commandes
        // Pour l'instant, on retourne false
        return false;
    }

    /**
     * Obtenir le nombre de précommandes en attente (placeholder)
     */
    public function getPendingPreordersCount(): int
    {
        // À implémenter avec le module Orders
        return 0;
    }
}
