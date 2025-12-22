<?php

declare(strict_types=1);

namespace Modules\Catalogue\Enums;

/**
 * Preorder Status Enumeration
 *
 * Gère les différents états d'une précommande
 */
enum PreorderStatus: string
{
    /**
     * En attente - Précommande ouverte
     */
    case PENDING = 'pending';

    /**
     * Active - Précommande en cours
     */
    case ACTIVE = 'active';

    /**
     * Fermée - Limite atteinte ou date dépassée
     */
    case CLOSED = 'closed';

    /**
     * Annulée - Précommande annulée par l'admin
     */
    case CANCELLED = 'cancelled';

    /**
     * Complétée - Stock reçu, produit disponible
     */
    case COMPLETED = 'completed';

    /**
     * Retourne le label lisible du statut
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::ACTIVE => 'Active',
            self::CLOSED => 'Fermée',
            self::CANCELLED => 'Annulée',
            self::COMPLETED => 'Terminée',
        };
    }

    /**
     * Retourne la couleur du badge pour l'affichage
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::ACTIVE => 'blue',
            self::CLOSED => 'gray',
            self::CANCELLED => 'red',
            self::COMPLETED => 'green',
        };
    }

    /**
     * Vérifie si les clients peuvent précommander
     */
    public function acceptsOrders(): bool
    {
        return in_array($this, [self::PENDING, self::ACTIVE]);
    }

    /**
     * Vérifie si la précommande est terminée
     */
    public function isFinished(): bool
    {
        return in_array($this, [self::CLOSED, self::CANCELLED, self::COMPLETED]);
    }
}
