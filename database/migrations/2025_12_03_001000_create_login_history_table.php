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
        Schema::create('login_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('user'); // user_id + user_type (Customer or User)
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->string('device_type', 50)->nullable(); // mobile, desktop, tablet
            $table->string('device_name')->nullable(); // iPhone 14, Chrome Windows, etc.
            $table->string('browser', 100)->nullable();
            $table->string('platform', 100)->nullable(); // iOS, Windows, Android
            $table->string('country', 100)->nullable();
            $table->string('country_code', 2)->nullable(); // ISO code
            $table->string('city', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_new_device')->default(false);
            $table->boolean('is_new_location')->default(false);
            $table->boolean('is_suspicious')->default(false);
            $table->timestamp('login_at');
            $table->timestamp('logout_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'user_type']);
            $table->index('login_at');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_history');
    }
};
