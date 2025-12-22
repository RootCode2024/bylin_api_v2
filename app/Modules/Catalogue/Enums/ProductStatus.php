<?php

declare(strict_types=1);

namespace Modules\Catalogue\Enums;

/**
 * Product Status Enumeration
 *
 * Gère les différents états d'un produit dans le catalogue
 */
enum ProductStatus: string
{
    /**
     * Brouillon - Produit non publié
     */
    case DRAFT = 'draft';

    /**
     * Actif - Produit publié et disponible
     */
    case ACTIVE = 'active';

    /**
     * Inactif - Produit masqué temporairement
     */
    case INACTIVE = 'inactive';

    /**
     * Rupture de stock - Stock épuisé (déclenche précommande auto)
     */
    case OUT_OF_STOCK = 'out_of_stock';

    /**
     * Précommande - Disponible en précommande uniquement
     */
    case PREORDER = 'preorder';

    /**
     * Abandonné - Produit définitivement retiré
     */
    case DISCONTINUED = 'discontinued';

    /**
     * Retourne le label lisible du statut
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::ACTIVE => 'Actif',
            self::INACTIVE => 'Inactif',
            self::OUT_OF_STOCK => 'Rupture de stock',
            self::PREORDER => 'Précommande',
            self::DISCONTINUED => 'Abandonné',
        };
    }

    /**
     * Retourne la couleur du badge pour l'affichage
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::ACTIVE => 'green',
            self::INACTIVE => 'yellow',
            self::OUT_OF_STOCK => 'red',
            self::PREORDER => 'blue',
            self::DISCONTINUED => 'red',
        };
    }

    /**
     * Vérifie si le produit est disponible à l'achat
     */
    public function isAvailableForPurchase(): bool
    {
        return in_array($this, [self::ACTIVE, self::PREORDER]);
    }

    /**
     * Vérifie si le produit est visible sur le site
     */
    public function isVisible(): bool
    {
        return in_array($this, [self::ACTIVE, self::OUT_OF_STOCK, self::PREORDER]);
    }

    /**
     * Vérifie si le produit peut passer en précommande
     */
    public function canEnablePreorder(): bool
    {
        return in_array($this, [self::OUT_OF_STOCK, self::ACTIVE, self::INACTIVE]);
    }

    /**
     * Retourne tous les statuts disponibles
     */
    public static function all(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Retourne les statuts visibles publiquement
     */
    public static function publicStatuses(): array
    {
        return [
            self::ACTIVE->value,
            self::OUT_OF_STOCK->value,
            self::PREORDER->value,
        ];
    }

    /**
     * Retourne les statuts qui nécessitent du stock
     */
    public static function requiresStock(): array
    {
        return [
            self::ACTIVE->value,
        ];
    }
}
