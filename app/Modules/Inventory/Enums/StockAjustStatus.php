<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum StockReason: string
{
    case ADJUSTMENT = 'adjustment';
    case SALE = 'sale';
    case RETURN = 'return';
    case DAMAGED = 'damaged';
    case RESTOCK = 'restock';
    case LOST = 'lost';

    /**
     * Libellés utilisateur (pour l'API et l'UI)
     */
    public function label(): string
    {
        return match ($this) {
            self::ADJUSTMENT => 'Ajustement manuel',
            self::SALE => 'Vente / Réception',
            self::RETURN => 'Retour client',
            self::DAMAGED => 'Produit endommagé',
            self::RESTOCK => 'Produit restocké',
            self::LOST => 'Produit perdu',
        };
    }

    /**
     * Type de mouvement associé (pour cohérence métier)
     */
    public function defaultMovementType(): string
    {
        return match ($this) {
            self::SALE => 'out',
            self::RETURN, self::RESTOCK => 'in',
            default => 'adjustment',
        };
    }

    /**
     * Valeurs autorisées pour la validation
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
