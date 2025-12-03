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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->string('transaction_id')->unique()->nullable();
            $table->string('gateway')->default('fedapay'); // fedapay, stripe, cash, etc.
            $table->string('status')->default('pending'); // pending, processing, completed, failed, refunded
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('XOF'); // West African CFA Franc
            $table->string('payment_method')->nullable(); // mobile_money, card, etc.
            $table->json('gateway_response')->nullable(); // Store full response
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
            $table->index('order_id');
            $table->index('transaction_id');
            $table->index('gateway');
            $table->index('status');
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_id');
            $table->string('refund_id')->unique()->nullable(); // Gateway refund ID
            $table->decimal('amount', 12, 2);
            $table->string('reason')->nullable();
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->json('gateway_response')->nullable();
            $table->uuid('created_by')->nullable(); // Admin who initiated refund
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('restrict');
            $table->index('payment_id');
            $table->index('refund_id');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
    }
};
