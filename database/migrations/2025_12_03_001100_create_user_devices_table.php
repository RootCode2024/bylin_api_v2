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
        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('user'); // user_id + user_type
            $table->string('device_fingerprint', 64)->unique(); // Hash(IP + UserAgent + ...)
            $table->string('device_name');
            $table->string('device_type', 50); // mobile, desktop, tablet
            $table->string('browser', 100);
            $table->string('platform', 100);
            $table->string('last_ip', 45);
            $table->string('last_country', 100)->nullable();
            $table->string('last_city', 100)->nullable();
            $table->timestamp('first_seen_at')->default(now());
            $table->timestamp('last_seen_at')->default(now());
            $table->boolean('is_trusted')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->string('blocked_reason')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'user_type']);
            $table->index('device_fingerprint');
            $table->index('is_trusted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
