<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\StockMovement;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Models\ProductVariation;

class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Initial Stock for Simple Products
        $products = Product::all();

        foreach ($products as $product) {
            if ($product->track_inventory && $product->stock_quantity > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'quantity' => $product->stock_quantity,
                    'quantity_before' => $product->stock_quantity,
                    'quantity_after' => $product->stock_quantity,
                    'type' => 'adjustment', // or 'purchase'
                    'reason' => 'Stock Initial (Seeding)',
                    'reference_id' => null,
                    'created_by' => null, // System
                ]);
            }
        }

        // 2. Initial Stock for Variations
        $variations = ProductVariation::all();

        foreach ($variations as $variation) {
            if ($variation->stock_quantity > 0) {
                StockMovement::create([
                    'product_id' => $variation->product_id,
                    'variation_id' => $variation->id,
                    'quantity' => $variation->stock_quantity,
                    'quantity_before' => $variation->stock_quantity,
                    'quantity_after' => $variation->stock_quantity,
                    'type' => 'adjustment',
                    'reason' => 'Stock Initial Variation (Seeding)',
                    'reference_id' => null,
                ]);
            }
        }
    }
}
