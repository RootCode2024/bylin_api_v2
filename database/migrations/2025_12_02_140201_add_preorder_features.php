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
        // Extend products table for preorder functionality
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_preorder_enabled')->default(false)->after('status');
            $table->boolean('is_preorder_auto')->default(false)->after('is_preorder_enabled');
            $table->date('preorder_available_date')->nullable()->after('is_preorder_auto');
            $table->integer('preorder_limit')->nullable()->after('preorder_available_date');
            $table->integer('preorder_count')->default(0)->after('preorder_limit');
            $table->text('preorder_terms')->nullable()->after('preorder_count');
            
            $table->index('is_preorder_enabled');
            $table->index(['is_preorder_enabled', 'preorder_available_date']);
        });

        // Extend order_items for preorder tracking
        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_preorder')->default(false)->after('total');
            $table->date('expected_availability_date')->nullable()->after('is_preorder');
            $table->string('preorder_status')->nullable()->after('expected_availability_date');
            
            $table->index('is_preorder');
            $table->index('preorder_status');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['is_preorder']);
            $table->dropIndex(['preorder_status']);
            $table->dropColumn(['is_preorder', 'expected_availability_date', 'preorder_status']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_preorder_enabled']);
            $table->dropIndex(['is_preorder_enabled', 'preorder_available_date']);
            $table->dropColumn([
                'is_preorder_enabled',
                'is_preorder_auto',
                'preorder_available_date',
                'preorder_limit',
                'preorder_count',
                'preorder_terms',
            ]);
        });
    }
};
