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

        for ($i = 0; $i < 100; $i++) {

            if ($i < 50) {
                $status = 'approved';
            } elseif ($i < 90) {
                $status = 'pending';
            } else {
                $status = 'rejected';
            }

            Review::create([
                'customer_id' => $customers->random()->id,
                'product_id' => $products->random()->id,
                'rating' => $faker->numberBetween(3, 5),
                'title' => $faker->sentence(3),
                'comment' => $faker->paragraph(2),
                'status' => $status,
                'is_verified_purchase' => $faker->boolean(70),
            ]);
        }
    }
}
