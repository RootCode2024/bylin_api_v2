<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // OAuth provider fields
            $table->string('oauth_provider')->nullable()->after('password');
            $table->string('oauth_provider_id')->nullable()->after('oauth_provider');
            $table->string('avatar_url')->nullable()->after('avatar');
            
            // Make password nullable for OAuth-only accounts
            $table->string('password')->nullable()->change();
            
            // Add composite index for OAuth lookups
            $table->index(['oauth_provider', 'oauth_provider_id'], 'oauth_provider_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('oauth_provider_idx');
            $table->dropColumn(['oauth_provider', 'oauth_provider_id', 'avatar_url']);
            
            // Restore password as required (may fail if NULL values exist)
            $table->string('password')->nullable(false)->change();
        });
    }
};
