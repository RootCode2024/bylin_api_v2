<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

/**
 * Base enum class for type-safe constants
 */
abstract class BaseEnum
{
    /**
     * Get all enum values
     */
    public static function values(): array
    {
        $reflection = new \ReflectionClass(static::class);
        return array_values($reflection->getConstants());
    }

    /**
     * Get all enum keys
     */
    public static function keys(): array
    {
        $reflection = new \ReflectionClass(static::class);
        return array_keys($reflection->getConstants());
    }

    /**
     * Check if value is valid
     */
    public static function isValid($value): bool
    {
        return in_array($value, static::values(), true);
    }

    /**
     * Get as associative array
     */
    public static function toArray(): array
    {
        $reflection = new \ReflectionClass(static::class);
        return $reflection->getConstants();
    }
}
