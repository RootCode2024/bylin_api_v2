<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            // ✅ Changer causer_id de bigint à uuid
            $table->dropColumn('causer_id');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            // ✅ Ajouter causer_id en uuid
            $table->uuid('causer_id')->nullable()->after('properties');
        });

        // ✅ Optionnel : Changer subject_id aussi si vous l'utilisez
        if (Schema::hasColumn('activity_log', 'subject_id')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->dropColumn('subject_id');
            });

            Schema::table('activity_log', function (Blueprint $table) {
                $table->uuid('subject_id')->nullable()->after('subject_type');
            });
        }
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn('causer_id');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->unsignedBigInteger('causer_id')->nullable();
        });

        if (Schema::hasColumn('activity_log', 'subject_id')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->dropColumn('subject_id');
            });

            Schema::table('activity_log', function (Blueprint $table) {
                $table->unsignedBigInteger('subject_id')->nullable();
            });
        }
    }
};
