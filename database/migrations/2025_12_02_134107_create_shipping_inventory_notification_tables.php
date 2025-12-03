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
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('carrier')->nullable(); // DHL, FedEx, etc.
            $table->json('rate_calculation')->nullable(); // Rules for calculating cost
            $table->decimal('base_cost', 10, 2)->default(0);
            $table->integer('estimated_delivery_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_active');
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('shipping_method_id');
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            $table->string('status')->default('pending'); // pending, shipped, in_transit, delivered, failed
            $table->json('tracking_events')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->date('shipped_date')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->date('delivered_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
            $table->foreign('shipping_method_id')->references('id')->on('shipping_methods')->onDelete('restrict');
            $table->index('order_id');
            $table->index('tracking_number');
            $table->index('status');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id')->nullable();
            $table->uuid('variation_id')->nullable();
            $table->string('type'); // in, out, adjustment
            $table->string('reason'); // purchase, sale, return, adjustment, damaged
            $table->integer('quantity'); // Negative for out
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->uuid('reference_id')->nullable(); // Order ID, etc.
            $table->string('reference_type')->nullable(); // Order, Purchase, etc.
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('variation_id')->references('id')->on('product_variations')->onDelete('cascade');
            $table->index('product_id');
            $table->index('variation_id');
            $table->index('type');
            $table->index('created_at');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // order_confirmation, shipping_update, etc.
            $table->string('notifiable_type'); // Customer or User
            $table->uuid('notifiable_id');
            $table->string('channel'); // email, sms, push, database
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->json('data')->nullable(); // Notification payload
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('status');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('shipping_methods');
    }
};
