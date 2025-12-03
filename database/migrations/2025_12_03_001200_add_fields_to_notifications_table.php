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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('title')->after('type');
            $table->text('message')->after('title');
            $table->string('action_url')->nullable()->after('data');
            $table->string('action_text', 100)->nullable()->after('action_url');
            $table->string('icon', 50)->nullable()->after('action_text');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('icon');
            $table->json('metadata')->nullable()->after('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'message',
                'action_url',
                'action_text',
                'icon',
                'priority',
                'metadata',
            ]);
        });
    }
};
