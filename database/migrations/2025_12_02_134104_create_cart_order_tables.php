<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('coupon_code')->nullable();
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('shipping_amount')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->boolean('is_gift_cart')->default(false)->after('total');
            $table->string('gift_cart_token', 50)->unique()->nullable();
            $table->string('gift_cart_status', 20)->nullable();
            $table->unsignedBigInteger('gift_cart_target_amount')->nullable();
            $table->unsignedBigInteger('gift_cart_paid_amount')->default(0);
            $table->uuid('gift_cart_owner_id')->nullable();
            $table->text('gift_cart_message')->nullable();
            $table->timestamp('gift_cart_expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('gift_cart_owner_id')->references('id')->on('customers')->onDelete('set null');
            $table->index('customer_id');
            $table->index('session_id');
            $table->index('expires_at');
            $table->index('gift_cart_token');
            $table->index('gift_cart_status');
            $table->index('gift_cart_expires_at');
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cart_id');
            $table->uuid('product_id');
            $table->uuid('variation_id')->nullable();
            $table->integer('quantity');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('subtotal');
            $table->boolean('is_preorder')->default(false);
            $table->date('expected_availability_date')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();

            $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('variation_id')->references('id')->on('product_variations')->onDelete('set null');
            $table->index('cart_id');
            $table->index('is_preorder');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number')->unique();
            $table->uuid('customer_id');
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending');
            $table->string('payment_method')->nullable();

            // Customer info snapshot
            $table->string('customer_email');
            $table->string('customer_phone');

            // Addresses
            $table->json('shipping_address');
            $table->json('billing_address');

            // Amounts
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('shipping_amount')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            // Coupon
            $table->string('coupon_code')->nullable();

            // Notes
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->index('order_number');
            $table->index('customer_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('product_id');
            $table->uuid('variation_id')->nullable();

            // Product snapshot
            $table->string('product_name');
            $table->string('product_sku');
            $table->string('variation_name')->nullable();

            $table->integer('quantity');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('total')->default(0);

            $table->boolean('is_preorder')->default(false);
            $table->date('expected_availability_date')->nullable();
            $table->string('preorder_status')->nullable();

            $table->json('options')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->index('order_id');
            $table->index('is_preorder');
            $table->index('preorder_status');
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->string('status');
            $table->text('note')->nullable();
            $table->uuid('created_by')->nullable(); // Admin user who changed status
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
