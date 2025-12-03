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
        // Extend carts table for gift cart functionality
        Schema::table('carts', function (Blueprint $table) {
            $table->boolean('is_gift_cart')->default(false)->after('total');
            $table->string('gift_cart_token', 50)->unique()->nullable()->after('is_gift_cart');
            $table->string('gift_cart_status', 20)->nullable()->after('gift_cart_token');
            $table->decimal('gift_cart_target_amount', 12, 2)->nullable()->after('gift_cart_status');
            $table->decimal('gift_cart_paid_amount', 12, 2)->default(0)->after('gift_cart_target_amount');
            $table->uuid('gift_cart_owner_id')->nullable()->after('gift_cart_paid_amount');
            $table->text('gift_cart_message')->nullable()->after('gift_cart_owner_id');
            $table->timestamp('gift_cart_expires_at')->nullable()->after('gift_cart_message');
            
            $table->foreign('gift_cart_owner_id')->references('id')->on('customers')->onDelete('set null');
            $table->index('gift_cart_token');
            $table->index('gift_cart_status');
            $table->index('gift_cart_expires_at');
        });

        // Create gift cart contributors table
        Schema::create('gift_cart_contributors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('gift_cart_id');
            $table->string('contributor_name');
            $table->string('contributor_email');
            $table->uuid('contributor_customer_id')->nullable();
            $table->decimal('contribution_amount', 12, 2);
            $table->decimal('contribution_percentage', 5, 2);
            $table->string('payment_status')->default('pending');
            $table->uuid('payment_id')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->foreign('gift_cart_id')->references('id')->on('carts')->onDelete('cascade');
            $table->foreign('contributor_customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
            $table->index('gift_cart_id');
            $table->index('contributor_email');
            $table->index('payment_status');
        });

        // Extend cart_items for preorder tracking
        Schema::table('cart_items', function (Blueprint $table) {
            $table->boolean('is_preorder')->default(false)->after('subtotal');
            $table->date('expected_availability_date')->nullable()->after('is_preorder');
            
            $table->index('is_preorder');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex(['is_preorder']);
            $table->dropColumn(['is_preorder', 'expected_availability_date']);
        });

        Schema::dropIfExists('gift_cart_contributors');

        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['gift_cart_owner_id']);
            $table->dropIndex(['gift_cart_token']);
            $table->dropIndex(['gift_cart_status']);
            $table->dropIndex(['gift_cart_expires_at']);
            $table->dropColumn([
                'is_gift_cart',
                'gift_cart_token',
                'gift_cart_status',
                'gift_cart_target_amount',
                'gift_cart_paid_amount',
                'gift_cart_owner_id',
                'gift_cart_message',
                'gift_cart_expires_at',
            ]);
        });
    }
};
