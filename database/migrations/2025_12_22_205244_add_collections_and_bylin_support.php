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
        /*
        |--------------------------------------------------------------------------
        | 1. ADD is_bylin_brand TO brands TABLE
        |--------------------------------------------------------------------------
        */
        Schema::table('brands', function (Blueprint $table) {
            $table->boolean('is_bylin_brand')
                ->default(false)
                ->after('slug')
                ->comment('Identifie si c\'est la marque Bylin (codes authenticité requis)');

            $table->index('is_bylin_brand');
        });

        /*
        |--------------------------------------------------------------------------
        | 2. CREATE collections TABLE (for Bylin products only)
        |--------------------------------------------------------------------------
        */
        Schema::create('collections', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Basic info
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Collection metadata
            $table->string('season')->nullable()->comment('Ex: Printemps 2024, Hiver 2025');
            $table->string('theme')->nullable()->comment('Ex: Urban, Streetwear, Classic');
            $table->date('release_date')->nullable();
            $table->date('end_date')->nullable()->comment('Date de fin de disponibilité');

            // Media
            $table->string('cover_image')->nullable();
            $table->string('banner_image')->nullable();

            // Status & visibility
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);

            // Stats
            $table->integer('products_count')->default(0);
            $table->integer('total_stock')->default(0);

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();

            // Extra data
            $table->json('meta_data')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('slug');
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('release_date');
            $table->index('sort_order');
        });

        /*
        |--------------------------------------------------------------------------
        | 3. ADD collection_id TO products TABLE
        |--------------------------------------------------------------------------
        */
        Schema::table('products', function (Blueprint $table) {
            $table->foreignUuid('collection_id')
                ->nullable()
                ->after('brand_id')
                ->constrained('collections')
                ->nullOnDelete()
                ->comment('Collection Bylin (uniquement pour produits Bylin)');

            $table->index('collection_id');
            $table->index(['brand_id', 'collection_id']);
        });

        /*
        |--------------------------------------------------------------------------
        | 4. ADD collection_id TO product_authenticity_codes (optionnel mais utile)
        |--------------------------------------------------------------------------
        */
        Schema::table('product_authenticity_codes', function (Blueprint $table) {
            $table->foreignUuid('collection_id')
                ->nullable()
                ->after('product_id')
                ->constrained('collections')
                ->nullOnDelete()
                ->comment('Lien vers la collection pour tracking');

            $table->index('collection_id');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::table('product_authenticity_codes', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
            $table->dropIndex(['collection_id']);
            $table->dropColumn('collection_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
            $table->dropIndex(['collection_id']);
            $table->dropIndex(['brand_id', 'collection_id']);
            $table->dropColumn('collection_id');
        });

        Schema::dropIfExists('collections');

        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex(['is_bylin_brand']);
            $table->dropColumn('is_bylin_brand');
        });
    }
};
