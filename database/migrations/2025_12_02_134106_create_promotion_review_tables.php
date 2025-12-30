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
        Schema::create('promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique()->nullable(); // Coupon code if applicable
            $table->text('description')->nullable();
            $table->string('type')->default('percentage'); // percentage, fixed_amount or buy_x_get_y
            $table->unsignedBigInteger('value');
            $table->unsignedBigInteger('min_purchase_amount')->nullable();
            $table->unsignedBigInteger('max_discount_amount')->nullable();
            $table->unsignedBigInteger('usage_limit')->nullable(); // Total usage limit
            $table->unsignedBigInteger('usage_limit_per_customer')->default(1);
            $table->unsignedBigInteger('usage_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('applicable_products')->nullable(); // Product IDs
            $table->json('applicable_categories')->nullable(); // Category IDs
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_active');
            $table->index(['starts_at', 'expires_at']);
        });

        Schema::create('promotion_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('promotion_id');
            $table->uuid('customer_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->timestamps();

            $table->foreign('promotion_id')->references('id')->on('promotions')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->index('promotion_id');
            $table->index('customer_id');
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('customer_id');
            $table->uuid('order_id')->nullable(); // Verified purchase if linked to order
            $table->integer('rating'); // 1-5
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->boolean('is_verified_purchase')->default(false);
            $table->integer('helpful_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->index('product_id');
            $table->index('customer_id');
            $table->index('status');
            $table->index('rating');
        });

        Schema::create('review_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('review_id');
            $table->string('media_type'); // image, video
            $table->string('media_path');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('review_id')->references('id')->on('reviews')->onDelete('cascade');
            $table->index('review_id');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('review_media');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('promotion_usages');
        Schema::dropIfExists('promotions');
    }
};
