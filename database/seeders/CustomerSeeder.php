<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Address;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create('fr_FR');
        
        // 1. Create a "Main" Customer for manual testing
        $mainCustomer = Customer::create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'customer@bylin.com',
            'password' => Hash::make('password'),
            'phone' => '+22997000000',
            'status' => 'active',
        ]);
        $mainCustomer->assignRole('customer');

        // Main Customer Address
        Address::create([
            'customer_id' => $mainCustomer->id,
            'type' => 'shipping',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'phone' => '+22997000000',
            'address_line_1' => '123 Rue du Commerce',
            'city' => 'Cotonou',
            'country' => 'Benin',
            'is_default' => true,
        ]);

        // 2. Create 100 Random Customers
        for ($i = 0; $i < 100; $i++) {
            $customer = Customer::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password'),
                'phone' => $faker->phoneNumber,
                'status' => 'active',
            ]);
            $customer->assignRole('customer');

            // 2.1 Addresses (1 to 3)
            $addressCount = rand(1, 3);
            for ($j = 0; $j < $addressCount; $j++) {
                Address::create([
                    'customer_id' => $customer->id,
                    'type' => $j === 0 ? 'shipping' : $faker->randomElement(['shipping', 'billing']),
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'phone' => $customer->phone,
                    'address_line_1' => $faker->streetAddress,
                    'city' => $faker->city,
                    'country' => 'France', // Faker FR
                    'is_default' => $j === 0,
                ]);
            }

            // 2.2 Devices
            \Modules\Security\Models\UserDevice::create([
                'user_id' => $customer->id,
                'user_type' => get_class($customer),
                'device_fingerprint' => Str::random(32),
                'device_name' => $faker->randomElement(['iPhone 13', 'Samsung Galaxy S21', 'Chrome on Windows']),
                'device_type' => $faker->randomElement(['mobile', 'desktop']),
                'platform' => $faker->randomElement(['iOS', 'Android', 'Windows']),
                'browser' => 'Chrome',
                'is_trusted' => true,
                'last_ip' => $faker->ipv4,
                'last_seen_at' => now(),
            ]);

            // 2.3 Wishlist
            $products = \Modules\Catalogue\Models\Product::inRandomOrder()->take(rand(1, 5))->pluck('id');
            // Logic normally handled by service, simulating DB directly
            foreach ($products as $productId) {
                 \Modules\Customer\Models\Wishlist::create([
                     'customer_id' => $customer->id,
                     'product_id' => $productId,
                 ]);
            }
        }
    }
}
