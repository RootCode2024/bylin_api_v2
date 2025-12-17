<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Promotion\Models\Promotion;
use Modules\Catalogue\Models\Category;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Coupon: WELCOME10
        Promotion::create([
            'name' => 'Bienvenue 10%',
            'code' => 'WELCOME10',
            'description' => '10% de réduction pour votre première commande.',
            'type' => 'percentage',
            'value' => 10,
            'min_purchase_amount' => 0,
            'is_active' => true,
            'starts_at' => now(),
            'expires_at' => null, // Forever
            'usage_limit' => 1000,
        ]);

        // 2. Coupon: NOEL2025
        Promotion::create([
            'name' => 'Noël 2025',
            'code' => 'NOEL2025',
            'description' => '20% sur tout le site.',
            'type' => 'percentage',
            'value' => 20,
            'min_purchase_amount' => 50000,
            'is_active' => true,
            'starts_at' => now()->startOfMonth(),
            'expires_at' => now()->endOfMonth(),
            'usage_limit' => 500,
        ]);

        // 3. Fixed Amount: MISEAJOUR
        Promotion::create([
            'name' => 'Réduction 5000F',
            'code' => 'MISEAJOUR',
            'description' => '5000 FCFA de réduction immédiate.',
            'type' => 'fixed_amount',
            'value' => 5000,
            'min_purchase_amount' => 20000,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        // 4. Category Discounts (simulated fields assuming promotion model structure)
        // Usually automatic promotions map to rules, simulating basic usage here
        $category = Category::where('slug', 'homme-vetements')->first();
        if ($category) {
            Promotion::create([
                'name' => 'Promo Homme (Auto)',
                'code' => null, // Automatic promo
                'description' => '15% sur les vêtements homme.',
                'type' => 'percentage',
                'value' => 15,
                'applicable_categories' => [$category->id],
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addWeek(),
            ]);
        }
    }
}
