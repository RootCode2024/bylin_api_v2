<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Customer\Models\Customer;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Test Customer
        $customer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'customer@bylin.com',
            'password' => Hash::make('password'),
            'phone' => '+33699887766',
            'status' => 'active',
        ]);
        $customer->assignRole('customer');
    }
}
