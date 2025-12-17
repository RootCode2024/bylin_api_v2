<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Reviews\Models\Review;
use Modules\Customer\Models\Customer;
use Modules\Catalogue\Models\Product;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $products = Product::all();
        $faker = \Faker\Factory::create('fr_FR');

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }

        // Create 100 Reviews
        for ($i = 0; $i < 100; $i++) {
            Review::create([
                'customer_id' => $customers->random()->id,
                'product_id' => $products->random()->id,
                'rating' => $faker->numberBetween(3, 5),
                'title' => $faker->sentence(3),
                'comment' => $faker->paragraph(2),
                'status' => 'approved',
                'is_verified_purchase' => $faker->boolean(70),
            ]);
        }
    }
}
