<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum StockOperation: string
{
    case SET = 'set';
    case ADD = 'add';
    case SUB = 'sub';

    public function label(): string
    {
        return match ($this) {
            self::SET => 'Définir à',
            self::ADD => 'Ajouter (+)',
            self::SUB => 'Retirer (-)',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SET => 'i-heroicons-equals',
            self::ADD => 'i-heroicons-plus',
            self::SUB => 'i-heroicons-minus',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
