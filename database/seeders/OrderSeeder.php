<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Customer\Models\Customer;
use Modules\Catalogue\Models\Product;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $products = Product::all();
        $faker = \Faker\Factory::create('fr_FR');

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }

        // Create 30 Orders
        for ($i = 0; $i < 30; $i++) {
            $customer = $customers->random();
            $status = $faker->randomElement([
                Order::STATUS_PENDING,
                Order::STATUS_PROCESSING,
                Order::STATUS_CONFIRMED,
                Order::STATUS_SHIPPED,
                Order::STATUS_DELIVERED,
                Order::STATUS_CANCELLED
            ]);

            // Payment status logic based on order status
            $paymentStatus = Order::PAYMENT_STATUS_PAID;
            if ($status === Order::STATUS_PENDING) {
                $paymentStatus = $faker->randomElement([Order::PAYMENT_STATUS_PENDING, Order::PAYMENT_STATUS_FAILED]);
            }

            $addressData = [
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'address' => $faker->streetAddress,
                'city' => $faker->city,
                'country' => 'Benin',
                'phone' => $customer->phone
            ];

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'customer_id' => $customer->id,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'payment_method' => $faker->randomElement(['card', 'mobile_money', 'cash_on_delivery']),
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'shipping_address' => $addressData,
                'billing_address' => $addressData,
                // These will be recalculated below
                'subtotal' => 0,
                'tax_amount' => 0,
                'shipping_amount' => 2000,
                'total' => 0,
            ]);

            // Add Order Items
            $itemCount = rand(1, 4);
            $subtotal = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products->random();
                $quantity = rand(1, 2);
                $price = $product->price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $price * $quantity,
                    'total' => $price * $quantity,
                ]);

                $subtotal += ($price * $quantity);
            }

            // Update Totals
            $order->update([
                'subtotal' => $subtotal,
                'total' => $subtotal + $order->shipping_amount,
            ]);
        }
    }
}
