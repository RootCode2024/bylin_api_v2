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
        Schema::create('gift_cart_contributors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('gift_cart_id');
            $table->string('contributor_name');
            $table->string('contributor_email');
            $table->uuid('contributor_customer_id')->nullable();
            $table->unsignedBigInteger('contribution_amount')->default(0);
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
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_cart_contributors');
    }
};
