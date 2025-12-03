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
        Schema::table('users', function (Blueprint $table) {
            // Change id to UUID
            $table->dropColumn('id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('status')->default('active')->after('email');
            $table->string('phone')->nullable()->after('email');
            $table->timestamp('email_verified_at')->nullable()->change();
            $table->softDeletes();
            
            $table->index('status');
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['status', 'phone']);
        });
    }
};
