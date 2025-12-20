<?php

declare(strict_types=1);

namespace Modules\Catalogue\Helpers;

use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Models\AttributeValue;

/**
 * Helper pour gérer l'affichage des couleurs de produits
 */
class ProductColorHelper
{
    /**
     * Récupère toutes les variations avec leurs détails de couleurs
     *
     * @param Product $product
     * @return array
     */
    public static function getColorVariations(Product $product): array
    {
        $variations = $product->variations()
            ->where('is_active', true)
            ->get();

        return $variations->map(function ($variation) {
            $colorCodes = $variation->attributes['color'] ?? [];
            $colorDetails = self::getColorDetails($colorCodes);

            return [
                'id' => $variation->id,
                'sku' => $variation->sku,
                'name' => $variation->variation_name,
                'price' => $variation->price,
                'compare_price' => $variation->compare_price,
                'stock' => $variation->stock_quantity,
                'is_available' => $variation->stock_quantity > 0,
                'is_multicolor' => count($colorCodes) > 1,
                'colors' => $colorDetails,
                'display' => [
                    'label' => self::formatColorLabel($colorDetails),
                    'hex_codes' => array_column($colorDetails, 'hex'),
                    'preview' => self::generateColorPreview($colorDetails)
                ]
            ];
        })->toArray();
    }

    /**
     * Récupère les détails des couleurs depuis la base de données
     *
     * @param array $colorCodes Ex: ['rouge', 'blanc', 'noir']
     * @return array
     */
    protected static function getColorDetails(array $colorCodes): array
    {
        if (empty($colorCodes)) {
            return [];
        }

        return AttributeValue::whereIn('value', $colorCodes)
            ->get()
            ->map(function ($color) {
                return [
                    'code' => $color->value,
                    'label' => $color->label ?? $color->value,
                    'hex' => $color->color_code
                ];
            })
            ->toArray();
    }

    /**
     * Formate le label d'affichage des couleurs
     *
     * @param array $colors
     * @return string
     */
    protected static function formatColorLabel(array $colors): string
    {
        if (empty($colors)) {
            return 'Sans couleur';
        }

        if (count($colors) === 1) {
            return $colors[0]['label'];
        }

        // Multicolore: "Rouge/Blanc/Noir"
        $labels = array_column($colors, 'label');
        return implode('/', $labels);
    }

    /**
     * Génère un aperçu HTML des couleurs
     *
     * @param array $colors
     * @return string
     */
    protected static function generateColorPreview(array $colors): string
    {
        if (empty($colors)) {
            return '';
        }

        $circles = array_map(function ($color) {
            $hex = $color['hex'] ?? '#CCCCCC';
            $label = $color['label'];

            return sprintf(
                '<span class="color-circle" style="background-color: %s;" title="%s"></span>',
                $hex,
                htmlspecialchars($label)
            );
        }, $colors);

        return implode('', $circles);
    }

    /**
     * Vérifie si un produit a des variations multicolores
     *
     * @param Product $product
     * @return bool
     */
    public static function hasMulticolorVariations(Product $product): bool
    {
        return $product->variations()
            ->where('is_active', true)
            ->get()
            ->contains(function ($variation) {
                $colors = $variation->attributes['color'] ?? [];
                return count($colors) > 1;
            });
    }

    /**
     * Récupère toutes les couleurs uniques d'un produit
     *
     * @param Product $product
     * @return array
     */
    public static function getAllUniqueColors(Product $product): array
    {
        $allColors = [];

        $product->variations()
            ->where('is_active', true)
            ->get()
            ->each(function ($variation) use (&$allColors) {
                $colors = $variation->attributes['color'] ?? [];
                $allColors = array_merge($allColors, $colors);
            });

        $uniqueColors = array_unique($allColors);

        return self::getColorDetails($uniqueColors);
    }

    /**
     * Format JSON pour l'API frontend
     *
     * @param Product $product
     * @return array
     */
    public static function toFrontendFormat(Product $product): array
    {
        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'base_price' => $product->price,
            'total_stock' => $product->stock_quantity,
            'has_variations' => $product->variations()->exists(),
            'variations' => self::getColorVariations($product),
            'all_colors' => self::getAllUniqueColors($product)
        ];
    }
}
